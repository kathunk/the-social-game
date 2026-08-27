"""Hand-size experiment: what do 6 or 10 tiles per player do vs the real 8?

Structural notes before any simulation:
- With 10 tiles the "both hands empty" draw is IMPOSSIBLE (20 tiles cannot
  fit on 16 spaces) — games can only end by victory or run forever.
- With 6 tiles the board-full draw arrives at 12 tiles on board, and hands
  empty (turn-skips) much sooner.

Measured: B3 self-play (balance, length, endings) and the motivated-staller
matchup (stalling) at hand sizes 6 / 8 / 10, on square + line.

Usage: python3 experiment_hand_size.py
"""

import random
from collections import Counter

import engine
import bots
from experiment_repetition import stall_tactician


def mirror(bot_a, bot_b, shape, games, label):
    res = Counter()
    first_wins = 0
    decisive = 0
    plies = 0
    for g in range(games):
        rng = random.Random(hash((label, shape, engine.HAND_SIZE, g)) & 0xFFFFFFFF)
        r = engine.play_game(bot_a, bot_b, shape, first=1, rng=rng)
        res[r["result"]] += 1
        plies += r["plies"]
        if len(r["winners"]) == 1:
            decisive += 1
            if r["winners"][0] == 1:
                first_wins += 1
    return {
        "decisive": decisive,
        "draws": res["draw"],
        "doubles": res["double"],
        "capped": res["capped"],
        "p1_rate": first_wins / decisive if decisive else 0.0,
        "avg_plies": plies / games,
    }


def main():
    header = (f"{'hand':>4} {'shape':<7} {'decisive':>8} {'draws':>6} "
              f"{'doubles':>8} {'capped':>7} {'1st-mover':>10} {'avg plies':>10}")

    print("== B3 self-play (600 games/shape) ==")
    print(header)
    for hand_size in (6, 8, 10):
        engine.HAND_SIZE = hand_size
        for shape in ("square", "line"):
            r = mirror(bots.tactician_bot, bots.tactician_bot, shape, 600, "mirror")
            print(f"{hand_size:>4} {shape:<7} {r['decisive']:>8} {r['draws']:>6} "
                  f"{r['doubles']:>8} {r['capped']:>7} {r['p1_rate']:>10.1%} "
                  f"{r['avg_plies']:>10.1f}")
        print()

    print("== Motivated staller vs guard bot (200 games/shape) ==")
    print(header)
    for hand_size in (6, 8, 10):
        engine.HAND_SIZE = hand_size
        for shape in ("square", "line"):
            r = mirror(stall_tactician, bots.guard_bot, shape, 200, "stall")
            print(f"{hand_size:>4} {shape:<7} {r['decisive']:>8} {r['draws']:>6} "
                  f"{r['doubles']:>8} {r['capped']:>7} {r['p1_rate']:>10.1%} "
                  f"{r['avg_plies']:>10.1f}")
        print()

    engine.HAND_SIZE = 8


if __name__ == "__main__":
    main()
