"""Behavioral telemetry from strong self-play, to extract human-usable rules.

Usage: python3 analyze.py --games 150 --bot B3-tactician --shapes square line el zig pyramid
"""

import argparse
import random
from collections import Counter

import engine
from engine import (
    SHAPE_MASKS, bit, execute_slide, hand, is_victorious, popcount,
    valid_elephant_moves, valid_slides,
)
from bots import BOTS, winning_slides


def instrumented_game(bot1, bot2, shape, first, rng, stats, max_plies=200):
    masks = SHAPE_MASKS[shape]
    p1 = p2 = 0
    elephant = 6
    turn = first
    history = {}

    prev_threats = {1: 0, 2: 0}  # winning slides each player had after last turn

    for ply in range(1, max_plies + 1):
        key = (p1, p2, elephant, turn)
        history[key] = history.get(key, 0) + 1

        # Threat state at start of this turn: can `turn` win right now?
        my_wins_now = winning_slides(p1, p2, elephant, turn, masks)
        opp = 3 - turn

        # Was the mover under threat at the start of this turn?
        opp_wins_now = winning_slides(p1, p2, elephant, opp, masks)
        under_threat = len(opp_wins_now) > 0

        bot = bot1 if turn == 1 else bot2
        slide, dest = bot(p1, p2, elephant, turn, masks, rng, history)

        entry_occupied = bool((p1 | p2) & bit(engine.PATHS[slide][0]))
        np1, np2, _ = execute_slide(p1, p2, slide, turn)

        v1 = is_victorious(np1, masks)
        v2 = is_victorious(np2, masks)
        if v1 or v2:
            winners = [pl for pl, v in ((1, v1), (2, v2)) if v]
            if len(winners) == 1:
                w = winners[0]
                stats["wins"] += 1
                stats["win_plies"].append(ply)
                if w == turn:
                    stats["won_own_turn"] += 1
                    if len(my_wins_now) >= 2:
                        stats["won_from_fork"] += 1
                    elif len(my_wins_now) == 1:
                        stats["won_single_threat"] += 1
                    else:
                        stats["won_created_this_turn"] += 1
                    if entry_occupied:
                        stats["win_via_push"] += 1
                else:
                    stats["won_on_opponent_slide"] += 1
                wbits = np1 if w == 1 else np2
                for m in masks:
                    if wbits & m == m:
                        for s in range(1, 17):
                            if m & bit(s):
                                stats["win_squares"][s] += 1
                        break
                stats["winner_hand"].append(hand(wbits))
            else:
                stats["doubles"] += 1
            return

        if hand(np1) == 0 and hand(np2) == 0:
            stats["draws"] += 1
            return

        # Threat neutralization tracking: mover was under threat, game didn't
        # end — did the opponent still have a winning slide after the full turn?
        opp_wins_after = winning_slides(np1, np2, dest, opp, masks)
        if under_threat:
            stats["threat_faced"] += 1
            if not opp_wins_after:
                stats["threat_neutralized"] += 1
                # How? elephant onto a winning-slide path vs tile disruption
                paths = set()
                for ws in opp_wins_now:
                    paths.update(engine.PATHS[ws])
                if dest in paths:
                    stats["neutralized_by_elephant"] += 1
        if len(opp_wins_after) >= 2:
            stats["fork_allowed"] += 1

        p1, p2, elephant = np1, np2, dest
        opp_bits = p1 if opp == 1 else p2
        if hand(opp_bits) > 0:
            turn = opp
    stats["capped"] += 1


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--games", type=int, default=150)
    parser.add_argument("--bot", default="B3-tactician")
    parser.add_argument("--shapes", nargs="*", default=engine.ALL_SHAPES)
    args = parser.parse_args()

    bot = BOTS[args.bot]
    for shape in args.shapes:
        stats = Counter()
        stats["win_plies"] = []
        stats["winner_hand"] = []
        stats["win_squares"] = Counter()
        first_moves = Counter()

        for g in range(args.games):
            rng = random.Random(hash((shape, g, "analyze")) & 0xFFFFFFFF)
            fm = bot(0, 0, 6, 1, SHAPE_MASKS[shape], rng, {})
            first_moves[fm[0]] += 1
            instrumented_game(bot, bot, shape, 1 + g % 2, rng, stats)

        wins = stats["wins"] or 1
        own = stats["won_own_turn"] or 1
        print(f"\n===== {shape} ({args.bot}, {args.games} games) =====")
        print(f"decisive {stats['wins']}  doubles {stats['doubles']}  "
              f"draws {stats['draws']}  capped {stats['capped']}")
        if stats["win_plies"]:
            avg = sum(stats["win_plies"]) / len(stats["win_plies"])
            print(f"avg winning ply {avg:.1f}   "
                  f"winner hand at end {sum(stats['winner_hand'])/wins:.1f}")
        print(f"won on own turn {stats['won_own_turn']}/{wins}"
              f"  (on opponent's slide: {stats['won_on_opponent_slide']})")
        print(f"  of own-turn wins: from fork {stats['won_from_fork']/own:.0%}, "
              f"single standing threat {stats['won_single_threat']/own:.0%}, "
              f"created+converted same turn {stats['won_created_this_turn']/own:.0%}")
        print(f"  wins via push-cascade {stats['win_via_push']/own:.0%}")
        tf = stats["threat_faced"]
        if tf:
            print(f"threats faced {tf}, neutralized {stats['threat_neutralized']/tf:.0%} "
                  f"(elephant used in {stats['neutralized_by_elephant']}/{stats['threat_neutralized']})")
        print(f"double-threat (fork) allowed events: {stats['fork_allowed']}")

        sq = stats["win_squares"]
        total = sum(sq.values()) or 1
        grid = "\n".join(
            "  ".join(f"{sq[r * 4 + c + 1] / total:4.0%}" for c in range(4))
            for r in range(4)
        )
        print(f"winning-config square usage:\n{grid}")
        print("first-move choices:", first_moves.most_common(5))


if __name__ == "__main__":
    main()
