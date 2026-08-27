"""Elephant in the Room — minimal rules engine for bot research.

Faithful to app/Challenges/ElephantInTheRoom/Support/BoardLogic.php (canonical):
- 4x4 board, spaces 1-16 in reading order. Elephant starts on 6.
- Each player has 8 tiles. Hand is fully derived: hand = 8 - own tiles on board
  (pushed-off tiles return to the owner's hand).
- A turn = slide a tile (victory checked immediately) + move the elephant
  (adjacent or stay; occupied spaces allowed). Turn passes unless the opponent's
  hand is empty, in which case the current player keeps going.
- The elephant blocks a slide if it sits at the entry, or at position k with
  positions 0..k-1 all occupied. Cascades only push contiguous tiles from entry.
- Both players share the same victory shape; both can win at once (draw).
- Both hands empty with no victor = draw.

Board representation: two 16-bit ints (bit i = space i+1), elephant as a space
number 1-16, mover as 1 or 2.
"""

import random

# ---------------------------------------------------------------------------
# Bit helpers

POPCOUNT = [bin(i).count("1") for i in range(1 << 16)]


def bit(space):
    return 1 << (space - 1)


def popcount(bits):
    return POPCOUNT[bits]


# ---------------------------------------------------------------------------
# Geometry

ADJACENT = {
    1: [2, 5], 2: [1, 3, 6], 3: [2, 4, 7], 4: [3, 8],
    5: [1, 6, 9], 6: [2, 5, 7, 10], 7: [3, 6, 8, 11], 8: [4, 7, 12],
    9: [5, 10, 13], 10: [6, 9, 11, 14], 11: [7, 10, 12, 15], 12: [8, 11, 16],
    13: [9, 14], 14: [10, 13, 15], 15: [11, 14, 16], 16: [12, 15],
}

# (entry_space, direction) -> 4-space path, verbatim from BoardLogic.php
PATHS = {
    (1, "down"): (1, 5, 9, 13),
    (2, "down"): (2, 6, 10, 14),
    (3, "down"): (3, 7, 11, 15),
    (4, "down"): (4, 8, 12, 16),
    (1, "right"): (1, 2, 3, 4),
    (5, "right"): (5, 6, 7, 8),
    (9, "right"): (9, 10, 11, 12),
    (13, "right"): (13, 14, 15, 16),
    (4, "left"): (4, 3, 2, 1),
    (8, "left"): (8, 7, 6, 5),
    (12, "left"): (12, 11, 10, 9),
    (16, "left"): (16, 15, 14, 13),
    (13, "up"): (13, 9, 5, 1),
    (14, "up"): (14, 10, 6, 2),
    (15, "up"): (15, 11, 7, 3),
    (16, "up"): (16, 12, 8, 4),
}

ALL_SLIDES = list(PATHS.keys())

# Per-slide prefix occupancy masks: PREFIX[slide][k] = bits of path[0..k]
PREFIX = {
    slide: [
        sum(bit(s) for s in path[: k + 1]) for k in range(4)
    ]
    for slide, path in PATHS.items()
}

ADJ_MASK = {s: sum(bit(a) for a in ADJACENT[s]) for s in range(1, 17)}
ADJ_PAIRS = [
    (a, b) for a in range(1, 17) for b in ADJACENT[a] if b > a
]

# ---------------------------------------------------------------------------
# Victory shapes, verbatim from BoardLogic.php constants

SHAPE_SPACES = {
    "square": [
        [1, 2, 5, 6], [2, 3, 6, 7], [3, 4, 7, 8],
        [5, 6, 9, 10], [6, 7, 10, 11], [7, 8, 11, 12],
        [9, 10, 13, 14], [10, 11, 14, 15], [11, 12, 15, 16],
    ],
    "line": [
        [1, 5, 9, 13], [2, 6, 10, 14], [3, 7, 11, 15], [4, 8, 12, 16],
        [1, 2, 3, 4], [5, 6, 7, 8], [9, 10, 11, 12], [13, 14, 15, 16],
    ],
    "el": [
        [1, 2, 3, 7], [2, 3, 4, 8], [5, 6, 7, 11],
        [6, 7, 8, 12], [9, 10, 11, 15], [10, 11, 12, 16],
        [1, 5, 6, 7], [2, 6, 7, 8], [5, 9, 10, 11],
        [6, 10, 11, 12], [9, 13, 14, 15], [10, 14, 15, 16],
        [3, 5, 6, 7], [4, 6, 7, 8], [7, 9, 10, 11],
        [8, 10, 11, 12], [11, 13, 14, 15], [12, 14, 15, 16],
        [1, 2, 3, 5], [2, 3, 4, 6], [5, 6, 7, 9],
        [6, 7, 8, 10], [9, 10, 11, 13], [10, 11, 12, 14],
        [1, 2, 5, 9], [2, 3, 6, 10], [3, 4, 7, 11],
        [5, 6, 9, 13], [6, 7, 10, 14], [7, 8, 11, 15],
        [1, 2, 6, 10], [2, 3, 7, 11], [3, 4, 8, 12],
        [5, 6, 10, 14], [6, 7, 11, 15], [7, 8, 12, 16],
        [1, 5, 9, 10], [2, 6, 10, 11], [3, 7, 11, 12],
        [5, 9, 13, 14], [6, 10, 14, 15], [7, 11, 15, 16],
        [2, 6, 9, 10], [3, 7, 10, 11], [4, 8, 11, 12],
        [6, 10, 13, 14], [7, 11, 14, 15], [8, 12, 15, 16],
    ],
    "pyramid": [
        [1, 2, 3, 6], [2, 3, 4, 7], [5, 6, 7, 10],
        [6, 7, 8, 11], [9, 10, 11, 14], [10, 11, 12, 15],
        [2, 5, 6, 7], [3, 6, 7, 8], [6, 9, 10, 11],
        [7, 10, 11, 12], [10, 13, 14, 15], [11, 14, 15, 16],
        [1, 5, 6, 9], [2, 6, 7, 10], [3, 7, 8, 11],
        [5, 9, 10, 13], [6, 10, 11, 14], [7, 11, 12, 15],
        [2, 5, 6, 10], [3, 6, 7, 11], [4, 7, 8, 12],
        [6, 9, 10, 14], [7, 10, 11, 15], [8, 11, 12, 16],
    ],
    "zig": [
        [1, 2, 6, 7], [2, 3, 7, 8], [5, 6, 10, 11],
        [6, 7, 11, 12], [9, 10, 14, 15], [10, 11, 15, 16],
        [2, 3, 5, 6], [3, 4, 6, 7], [6, 7, 9, 10],
        [7, 8, 10, 11], [10, 11, 13, 14], [11, 12, 14, 15],
        [1, 5, 6, 10], [2, 6, 7, 11], [3, 7, 8, 12],
        [5, 9, 10, 14], [6, 10, 11, 15], [7, 11, 12, 16],
        [2, 5, 6, 9], [3, 6, 7, 10], [4, 7, 8, 11],
        [6, 9, 10, 13], [7, 10, 11, 14], [8, 11, 12, 15],
    ],
}

SHAPE_MASKS = {
    name: tuple(sum(bit(s) for s in config) for config in configs)
    for name, configs in SHAPE_SPACES.items()
}

ALL_SHAPES = ["square", "line", "el", "zig", "pyramid"]
BOT_SHAPES = ["square", "line", "el"]

# ---------------------------------------------------------------------------
# Rules

def valid_slides(occ, elephant):
    """Slides not blocked by the elephant. occ = p1 | p2."""
    result = []
    for slide in ALL_SLIDES:
        path = PATHS[slide]
        if elephant == path[0]:
            continue
        prefix = PREFIX[slide]
        blocked = False
        for k in (1, 2, 3):
            if elephant == path[k] and (occ & prefix[k - 1]) == prefix[k - 1]:
                blocked = True
                break
        if not blocked:
            result.append(slide)
    return result


def execute_slide(p1, p2, slide, mover):
    """Returns (p1, p2, pushed_off_owner or None)."""
    path = PATHS[slide]
    occ = p1 | p2
    owners = []
    for s in path:
        b = bit(s)
        owners.append(1 if p1 & b else (2 if p2 & b else 0))

    cascade = 0
    for o in owners:
        if o:
            cascade += 1
        else:
            break

    pushed = None
    if cascade == 4:
        pushed = owners[3]
        new_owners = [mover] + owners[0:3]
    else:
        new_owners = [mover] + owners[0:cascade] + owners[cascade + 1: 4]

    for s, o in zip(path, new_owners):
        b = bit(s)
        p1 &= ~b
        p2 &= ~b
        if o == 1:
            p1 |= b
        elif o == 2:
            p2 |= b
    return p1, p2, pushed


def valid_elephant_moves(elephant):
    return ADJACENT[elephant] + [elephant]


def is_victorious(bits, masks):
    for m in masks:
        if bits & m == m:
            return True
    return False


def hand(bits):
    return 8 - popcount(bits)


def next_turn_after(np1, np2, mover):
    """Who moves next after `mover` finishes a full turn on this board."""
    opponent = 3 - mover
    opp_bits = np1 if opponent == 1 else np2
    return opponent if hand(opp_bits) > 0 else mover


def play_game(bot1, bot2, shape, first=1, rng=None, max_plies=200,
              repetition_rule=None):
    """Play a full game. bot(p1, p2, elephant, mover, masks, rng, history) ->
    ((entry, direction), elephant_dest). Returns a result dict.

    history maps (p1, p2, elephant, turn) -> times seen, so bots can avoid
    (or exploit) repetition; history["_moves"] holds each player's slide
    sequence. The real game has no repetition rule; games exceeding
    max_plies are reported as "capped" (a de facto draw).

    repetition_rule (experimental variants, loss for the offender):
      "move3" — a player whose slide equals their own previous two slides
                loses immediately.
      "pos3"  — a player whose full turn recreates a position (tiles +
                elephant + player to move) for the third time loses.
    """
    rng = rng or random.Random()
    masks = SHAPE_MASKS[shape]
    p1 = p2 = 0
    elephant = 6
    turn = first
    plies = 0
    history = {"_moves": {1: [], 2: []}}

    while True:
        plies += 1
        if plies > max_plies:
            return {"result": "capped", "winners": [], "plies": plies}

        key = (p1, p2, elephant, turn)
        history[key] = history.get(key, 0) + 1

        bot = bot1 if turn == 1 else bot2
        slide, dest = bot(p1, p2, elephant, turn, masks, rng, history)

        assert slide in valid_slides(p1 | p2, elephant), f"invalid slide {slide}"

        my_moves = history["_moves"][turn]
        if (repetition_rule == "move3" and len(my_moves) >= 2
                and my_moves[-1] == my_moves[-2] == slide):
            return {"result": "repetition", "winners": [3 - turn],
                    "loser": turn, "plies": plies}
        my_moves.append(slide)

        p1, p2, _pushed = execute_slide(p1, p2, slide, turn)

        v1 = is_victorious(p1, masks)
        v2 = is_victorious(p2, masks)
        if v1 or v2:
            winners = [pl for pl, v in ((1, v1), (2, v2)) if v]
            return {
                "result": "win" if len(winners) == 1 else "double",
                "winners": winners,
                "plies": plies,
            }

        if hand(p1) == 0 and hand(p2) == 0:
            return {"result": "draw", "winners": [], "plies": plies}

        assert dest in valid_elephant_moves(elephant), f"invalid elephant {dest}"
        elephant = dest

        nxt = next_turn_after(p1, p2, turn)
        if repetition_rule == "pos3":
            if history.get((p1, p2, elephant, nxt), 0) >= 2:
                return {"result": "repetition", "winners": [3 - turn],
                        "loser": turn, "plies": plies}
        turn = nxt
