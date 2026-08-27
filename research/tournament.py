"""Round-robin bot tournament.

Usage:
    python3 tournament.py --games 500 --bots B0-random B1-current --shapes square line el
    python3 tournament.py --games 200                      # all bots, all shapes
    python3 tournament.py --mirror B1-current --games 1000 # self-play (first-move advantage)
"""

import argparse
import itertools
import random
import time

import engine
from bots import BOTS


def run_pairing(name_a, name_b, shape, games, seed=0):
    """Play `games` games of a vs b on `shape`, alternating first mover.
    Returns dict with wins for a, b, draws, avg plies."""
    bot_a, bot_b = BOTS[name_a], BOTS[name_b]
    wins_a = wins_b = draws = 0
    first_mover_wins = 0
    decisive = 0
    total_plies = 0

    for g in range(games):
        rng = random.Random(hash((name_a, name_b, shape, seed, g)) & 0xFFFFFFFF)
        a_is_p1 = g % 2 == 0
        bot1, bot2 = (bot_a, bot_b) if a_is_p1 else (bot_b, bot_a)
        result = engine.play_game(bot1, bot2, shape, first=1, rng=rng)
        total_plies += result["plies"]

        winners = result["winners"]
        if len(winners) == 1:
            decisive += 1
            if winners[0] == 1:
                first_mover_wins += 1
            winner_is_a = (winners[0] == 1) == a_is_p1
            if winner_is_a:
                wins_a += 1
            else:
                wins_b += 1
        else:
            draws += 1

    return {
        "a": wins_a,
        "b": wins_b,
        "draws": draws,
        "avg_plies": total_plies / games,
        "first_mover_rate": first_mover_wins / decisive if decisive else 0.0,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--games", type=int, default=200)
    parser.add_argument("--bots", nargs="*", default=list(BOTS.keys()))
    parser.add_argument("--shapes", nargs="*", default=engine.ALL_SHAPES)
    parser.add_argument("--mirror", type=str, default=None,
                        help="self-play a single bot to measure first-move advantage")
    parser.add_argument("--seed", type=int, default=0)
    args = parser.parse_args()

    if args.mirror:
        print(f"Self-play: {args.mirror}, {args.games} games/shape")
        print(f"{'shape':<10} {'p1 wins':>8} {'p2 wins':>8} {'draws':>6} "
              f"{'p1 rate':>8} {'avg plies':>10}")
        for shape in args.shapes:
            t0 = time.time()
            r = run_pairing(args.mirror, args.mirror, shape, args.games, args.seed)
            # in mirror mode a/b split is meaningless; use first_mover_rate
            dec = args.games - r["draws"]
            p1w = round(r["first_mover_rate"] * dec)
            print(f"{shape:<10} {p1w:>8} {dec - p1w:>8} {r['draws']:>6} "
                  f"{r['first_mover_rate']:>8.1%} {r['avg_plies']:>10.1f}"
                  f"   ({time.time() - t0:.1f}s)")
        return

    pairs = list(itertools.combinations(args.bots, 2))
    for name_a, name_b in pairs:
        print(f"\n=== {name_a} vs {name_b} ({args.games} games/shape) ===")
        print(f"{'shape':<10} {name_a:>14} {name_b:>14} {'draws':>6} "
              f"{'1st-mover':>10} {'avg plies':>10}")
        for shape in args.shapes:
            t0 = time.time()
            r = run_pairing(name_a, name_b, shape, args.games, args.seed)
            print(f"{shape:<10} {r['a']:>14} {r['b']:>14} {r['draws']:>6} "
                  f"{r['first_mover_rate']:>10.1%} {r['avg_plies']:>10.1f}"
                  f"   ({time.time() - t0:.1f}s)")


if __name__ == "__main__":
    main()
