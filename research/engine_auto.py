"""Auto-elephant variant of Elephant in the Room (the original design).

Rules delta vs the shipped game (see engine.py):
- There is NO shared elephant on the board and NO elephant-move phase.
- Each player has their own elephant sitting on the BORDER. After a player
  slides a tile in at entry space X, that player's elephant moves to X.
- Neither player may make a slide whose ENTRY space is currently occupied by
  either elephant. (Corner spaces ban both of their arrows.) Cascades may
  still push tiles into/through those board spaces — only the entry action
  is blocked.
- Everything else is identical: cascading, push-off returns to hand, victory
  checked after each placement, turn skip when the opponent's hand is empty,
  draw when both hands are empty.

State: (p1, p2, e1, e2, turn) where e1/e2 are the entry space of each
player's most recent slide (0 = hasn't played yet).
"""

import random

from engine import (
    ALL_SLIDES, SHAPE_MASKS, execute_slide, hand, is_victorious,
)


def valid_slides_auto(e1, e2):
    """All slides whose entry space is not blocked by either elephant."""
    return [s for s in ALL_SLIDES if s[0] != e1 and s[0] != e2]


def play_game_auto(bot1, bot2, shape, first=1, rng=None, max_plies=200):
    """bot(p1, p2, e1, e2, mover, masks, rng, history) -> (entry, direction).

    history maps (p1, p2, e1, e2, turn) -> times seen.
    """
    rng = rng or random.Random()
    masks = SHAPE_MASKS[shape]
    p1 = p2 = 0
    e1 = e2 = 0
    turn = first
    plies = 0
    history = {}

    while True:
        plies += 1
        if plies > max_plies:
            return {"result": "capped", "winners": [], "plies": plies}

        key = (p1, p2, e1, e2, turn)
        history[key] = history.get(key, 0) + 1

        bot = bot1 if turn == 1 else bot2
        slide = bot(p1, p2, e1, e2, turn, masks, rng, history)

        assert slide in valid_slides_auto(e1, e2), f"invalid slide {slide}"
        p1, p2, _pushed = execute_slide(p1, p2, slide, turn)
        if turn == 1:
            e1 = slide[0]
        else:
            e2 = slide[0]

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

        opponent = 3 - turn
        opp_bits = p1 if opponent == 1 else p2
        if hand(opp_bits) > 0:
            turn = opponent
