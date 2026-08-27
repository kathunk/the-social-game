"""Bots for the auto-elephant variant. Mirrors bots.py's B3 (and B4) but with
no elephant decision — a move is just a slide, and threat counting respects
the entry bans instead of board blocking.
"""

from engine import bit, execute_slide, hand, is_victorious, popcount
from engine_auto import valid_slides_auto

WIN = 1_000_000_000
PROGRESS = (0, 1, 4, 12, 0)


def _sides(p1, p2, mover):
    return (p1, p2) if mover == 1 else (p2, p1)


def winning_slides_auto(p1, p2, e1, e2, player, masks):
    wins = []
    for slide in valid_slides_auto(e1, e2):
        np1, np2, _ = execute_slide(p1, p2, slide, player)
        mine = np1 if player == 1 else np2
        if is_victorious(mine, masks):
            wins.append(slide)
    return wins


def evaluate_auto(p1, p2, e1, e2, mover, masks):
    """Static eval from `mover`'s perspective, opponent to act next."""
    me, opp = _sides(p1, p2, mover)
    opponent = 3 - mover
    score = 0

    opp_wins = len(winning_slides_auto(p1, p2, e1, e2, opponent, masks))
    score -= 120_000 * opp_wins

    my_threats = len(winning_slides_auto(p1, p2, e1, e2, mover, masks))
    score += 400 * my_threats
    if my_threats >= 2:
        score += 2_000

    for m in masks:
        mine = popcount(me & m)
        theirs = popcount(opp & m)
        if theirs == 0:
            score += PROGRESS[mine]
        if mine == 0:
            score -= PROGRESS[theirs]

    if popcount(me) == 8:
        score -= 600
    return score


def _repetition_penalty(history, np1, np2, ne1, ne2, mover):
    if not history:
        return 0
    opponent = 3 - mover
    opp_bits = np1 if opponent == 1 else np2
    next_turn = opponent if hand(opp_bits) > 0 else mover
    seen = history.get((np1, np2, ne1, ne2, next_turn), 0)
    return 800 * seen


def _moves_auto(p1, p2, e1, e2, mover, masks):
    for slide in valid_slides_auto(e1, e2):
        np1, np2, _ = execute_slide(p1, p2, slide, mover)
        ne1, ne2 = (slide[0], e2) if mover == 1 else (e1, slide[0])
        mine = np1 if mover == 1 else np2
        theirs = np2 if mover == 1 else np1
        my_win = is_victorious(mine, masks)
        their_win = is_victorious(theirs, masks)
        if my_win or their_win:
            immediate = 0 if (my_win and their_win) else (WIN if my_win else -WIN)
        else:
            immediate = None
        yield slide, np1, np2, ne1, ne2, immediate


def tactician_auto(p1, p2, e1, e2, mover, masks, rng, history=None):
    moves = list(_moves_auto(p1, p2, e1, e2, mover, masks))
    rng.shuffle(moves)
    best, best_score = None, None
    for slide, np1, np2, ne1, ne2, immediate in moves:
        if immediate is not None:
            score = immediate
        else:
            score = evaluate_auto(np1, np2, ne1, ne2, mover, masks)
            score -= _repetition_penalty(history, np1, np2, ne1, ne2, mover)
        if best_score is None or score > best_score:
            best, best_score = slide, score
    return best


def stall_tactician_auto(p1, p2, e1, e2, mover, masks, rng, history=None):
    """No repetition penalty: will fortress forever if the rules allow it."""
    return tactician_auto(p1, p2, e1, e2, mover, masks, rng, None)


TOP_K = 8


def _best_reply_auto(p1, p2, e1, e2, opponent, masks):
    best = None
    for slide, np1, np2, ne1, ne2, immediate in _moves_auto(
        p1, p2, e1, e2, opponent, masks
    ):
        if immediate is not None:
            score = immediate
        else:
            score = evaluate_auto(np1, np2, ne1, ne2, opponent, masks)
        if best is None or score > best:
            best = score
        if best >= WIN:
            break
    return best if best is not None else 0


def lookahead_auto(p1, p2, e1, e2, mover, masks, rng, history=None):
    moves = list(_moves_auto(p1, p2, e1, e2, mover, masks))
    rng.shuffle(moves)

    scored = []
    for slide, np1, np2, ne1, ne2, immediate in moves:
        if immediate is not None:
            score = immediate
        else:
            score = evaluate_auto(np1, np2, ne1, ne2, mover, masks)
        scored.append((score, slide, np1, np2, ne1, ne2, immediate))
    scored.sort(key=lambda t: t[0], reverse=True)

    if scored[0][0] >= WIN:
        return scored[0][1]

    best, best_score = None, None
    for score, slide, np1, np2, ne1, ne2, immediate in scored[:TOP_K]:
        if immediate is not None:
            total = score
        else:
            opponent = 3 - mover
            opp_bits = np1 if opponent == 1 else np2
            if hand(opp_bits) > 0:
                reply = _best_reply_auto(np1, np2, ne1, ne2, opponent, masks)
                total = -reply + 0.001 * score
            else:
                total = score
            total -= _repetition_penalty(history, np1, np2, ne1, ne2, mover)
        if best_score is None or total > best_score:
            best, best_score = slide, total
    return best
