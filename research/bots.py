"""Bot ladder for Elephant in the Room research.

Every bot has the signature:
    bot(p1, p2, elephant, mover, masks, rng) -> ((entry, direction), elephant_dest)

Ladder:
    B0 random_bot      — uniform random slide + elephant
    B1 current_bot     — faithful replica of the shipped bot (greedy slide
                         scoring, RANDOM elephant)
    B2 guard_bot       — B1's slide choice + a defensive elephant policy
    B3 tactician_bot   — joint (slide, elephant) 1-ply search, richer eval
    B4 lookahead_bot   — B3 + explicit opponent best-reply (2-ply)
"""

from engine import (
    ALL_SLIDES, ADJ_PAIRS, bit, execute_slide, hand, is_victorious,
    popcount, valid_elephant_moves, valid_slides,
)

WIN = 1_000_000_000


def _sides(p1, p2, mover):
    return (p1, p2) if mover == 1 else (p2, p1)


# ---------------------------------------------------------------------------
# Shared feature helpers

def adjacent_pairs(bits):
    count = 0
    for a, b in ADJ_PAIRS:
        if bits & bit(a) and bits & bit(b):
            count += 1
    return count


def has_check(bits, occ, elephant, masks):
    """3 of 4 config spaces owned, 4th empty and not the elephant.
    (The iOS/cleaned definition of the shipped bot's 'check'.)"""
    for m in masks:
        inter = bits & m
        if popcount(inter) == 3:
            missing = m & ~inter
            if not (occ & missing) and bit(elephant) != missing:
                return True
    return False


def winning_slides(p1, p2, elephant, player, masks):
    """Slides the given player could make right now that win immediately."""
    wins = []
    for slide in valid_slides(p1 | p2, elephant):
        np1, np2, _ = execute_slide(p1, p2, slide, player)
        mine = np1 if player == 1 else np2
        if is_victorious(mine, masks):
            wins.append(slide)
    return wins


# ---------------------------------------------------------------------------
# B0 — random

def random_bot(p1, p2, elephant, mover, masks, rng, history=None):
    slide = rng.choice(valid_slides(p1 | p2, elephant))
    dest = rng.choice(valid_elephant_moves(elephant))
    return slide, dest


# ---------------------------------------------------------------------------
# B1 — faithful replica of the shipped bot (hard difficulty)

def current_board_score(p1, p2, elephant, mover, masks):
    me, opp = _sides(p1, p2, mover)
    occ = p1 | p2
    score = 0
    if is_victorious(me, masks):
        score += WIN
    if is_victorious(opp, masks):
        score -= 1_000
    if has_check(me, occ, elephant, masks):
        score += 100
    if has_check(opp, occ, elephant, masks):
        score -= 200
    score += adjacent_pairs(me) - adjacent_pairs(opp)
    if popcount(me) == 8:
        score -= 500
    return score


def _current_best_slide(p1, p2, elephant, mover, masks, rng):
    slides = valid_slides(p1 | p2, elephant)
    rng.shuffle(slides)
    best, best_score = None, None
    for slide in slides:
        np1, np2, _ = execute_slide(p1, p2, slide, mover)
        score = current_board_score(np1, np2, elephant, mover, masks)
        if best_score is None or score > best_score:
            best, best_score = slide, score
    return best


def current_bot(p1, p2, elephant, mover, masks, rng, history=None):
    slide = _current_best_slide(p1, p2, elephant, mover, masks, rng)
    dest = rng.choice(valid_elephant_moves(elephant))
    return slide, dest


# ---------------------------------------------------------------------------
# B2 — B1's slide + defensive elephant
#
# Elephant policy: among the (at most 5) destinations, minimize the number of
# immediately winning slides the opponent would have; tiebreak by maximizing
# our own next-turn winning slides, then by sitting inside the opponent's
# strongest partial configs.

def _guard_elephant(p1, p2, elephant, mover, masks, rng):
    opponent = 3 - mover
    dests = valid_elephant_moves(elephant)
    rng.shuffle(dests)
    best, best_key = None, None
    for dest in dests:
        opp_wins = len(winning_slides(p1, p2, dest, opponent, masks))
        my_wins = len(winning_slides(p1, p2, dest, mover, masks))
        key = (-opp_wins, my_wins)
        if best_key is None or key > best_key:
            best, best_key = dest, key
    return best


def guard_bot(p1, p2, elephant, mover, masks, rng, history=None):
    slide = _current_best_slide(p1, p2, elephant, mover, masks, rng)
    np1, np2, _ = execute_slide(p1, p2, slide, mover)
    dest = _guard_elephant(np1, np2, elephant, mover, masks, rng)
    return slide, dest


# ---------------------------------------------------------------------------
# B3 — joint (slide, elephant) 1-ply search with a richer evaluation
#
# Evaluates the position handed to the opponent:
#   - each opponent immediate winning slide is close to fatal
#   - our own standing threats (winning slides if it were our turn) are strong,
#     two or more (a fork) much stronger
#   - "live config" progress: partial shapes not contaminated by the opponent
#     and not sat on by the elephant
#   - keep at least one tile in hand

PROGRESS = (0, 1, 4, 12, 0)  # value of 0..4 own tiles in a live config


def evaluate(p1, p2, elephant, mover, masks):
    """Static eval of the position from `mover`'s perspective, where it is the
    OPPONENT's turn to act (i.e. mover just finished their whole turn)."""
    me, opp = _sides(p1, p2, mover)
    opponent = 3 - mover
    score = 0

    opp_wins = len(winning_slides(p1, p2, elephant, opponent, masks))
    score -= 120_000 * opp_wins

    my_threats = len(winning_slides(p1, p2, elephant, mover, masks))
    score += 400 * my_threats
    if my_threats >= 2:
        score += 2_000

    ele_bit = bit(elephant)
    for m in masks:
        mine = popcount(me & m)
        theirs = popcount(opp & m)
        if theirs == 0 and not (m & ele_bit):
            score += PROGRESS[mine]
        if mine == 0 and not (m & ele_bit):
            score -= PROGRESS[theirs]

    if popcount(me) == 8:
        score -= 600
    return score


def _joint_moves(p1, p2, elephant, mover, masks):
    """Yield (slide, dest, np1, np2, immediate) for every joint move.
    immediate is +WIN/-WIN/0 for game-ending slides (win/loss/draw handled
    by caller via victory flags)."""
    for slide in valid_slides(p1 | p2, elephant):
        np1, np2, _ = execute_slide(p1, p2, slide, mover)
        mine = np1 if mover == 1 else np2
        theirs = np2 if mover == 1 else np1
        my_win = is_victorious(mine, masks)
        their_win = is_victorious(theirs, masks)
        if my_win or their_win:
            if my_win and their_win:
                immediate = 0  # double win = draw
            elif my_win:
                immediate = WIN
            else:
                immediate = -WIN
            yield slide, elephant, np1, np2, immediate
            continue
        for dest in valid_elephant_moves(elephant):
            yield slide, dest, np1, np2, None


def _repetition_penalty(history, np1, np2, dest, mover):
    """Discourage recreating a position we've already been in (the real game
    has no repetition rule, so cycles are otherwise stable)."""
    if not history:
        return 0
    opponent = 3 - mover
    opp_bits = np1 if opponent == 1 else np2
    next_turn = opponent if hand(opp_bits) > 0 else mover
    seen = history.get((np1, np2, dest, next_turn), 0)
    return 800 * seen


def tactician_bot(p1, p2, elephant, mover, masks, rng, history=None):
    moves = list(_joint_moves(p1, p2, elephant, mover, masks))
    rng.shuffle(moves)
    best, best_score = None, None
    for slide, dest, np1, np2, immediate in moves:
        if immediate is not None:
            score = immediate
        else:
            score = evaluate(np1, np2, dest, mover, masks)
            score -= _repetition_penalty(history, np1, np2, dest, mover)
        if best_score is None or score > best_score:
            best, best_score = (slide, dest), score
    return best


# ---------------------------------------------------------------------------
# B4 — 2-ply: my joint move, opponent's best joint reply (both eval'd with B3's
# function). Top-K pruning on my candidate moves keeps it fast.

TOP_K = 8


def _best_reply_score(p1, p2, elephant, opponent, masks):
    """Opponent's best achievable score (from THEIR perspective) replying to
    this position."""
    best = None
    for slide, dest, np1, np2, immediate in _joint_moves(
        p1, p2, elephant, opponent, masks
    ):
        if immediate is not None:
            score = immediate
        else:
            score = evaluate(np1, np2, dest, opponent, masks)
        if best is None or score > best:
            best = score
        if best >= WIN:
            break
    return best if best is not None else 0


def lookahead_bot(p1, p2, elephant, mover, masks, rng, history=None, top_k=None):
    top_k = top_k or TOP_K
    moves = list(_joint_moves(p1, p2, elephant, mover, masks))
    rng.shuffle(moves)

    scored = []
    for slide, dest, np1, np2, immediate in moves:
        if immediate is not None:
            score = immediate
        else:
            score = evaluate(np1, np2, dest, mover, masks)
        scored.append((score, slide, dest, np1, np2, immediate))
    scored.sort(key=lambda t: t[0], reverse=True)

    if scored[0][0] >= WIN:
        return scored[0][1], scored[0][2]

    best, best_score = None, None
    for score, slide, dest, np1, np2, immediate in scored[:top_k]:
        if immediate is not None:
            total = score
        else:
            opponent = 3 - mover
            opp_bits = np1 if opponent == 1 else np2
            if hand(opp_bits) > 0:
                reply = _best_reply_score(np1, np2, dest, opponent, masks)
                total = -reply + 0.001 * score
            else:
                total = score  # opponent is skipped; keep static eval
            total -= _repetition_penalty(history, np1, np2, dest, mover)
        if best_score is None or total > best_score:
            best, best_score = (slide, dest), total
    return best


BOTS = {
    "B0-random": random_bot,
    "B1-current": current_bot,
    "B2-guard": guard_bot,
    "B3-tactician": tactician_bot,
    "B4-lookahead": lookahead_bot,
}
