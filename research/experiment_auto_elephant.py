"""Auto-elephant variant experiment.

Hypothesis under test (John's): removing voluntary elephant movement
(each player's elephant auto-parks on the entry space of their last slide,
banning both players from entering there) will
  (a) drastically shorten games and eliminate stalling, but
  (b) over-simplify — play gets rote and first-mover advantage comes back.

Baseline (frozen at research-freeze-1), B3 self-play 1000 games/shape:
  square: 43.3% first-mover, 15% draws, 52.9 avg plies
  line:   59.2% first-mover, 10% draws, 46.6 avg plies

Usage: python3 experiment_auto_elephant.py
"""

import random
from collections import Counter

import engine_auto
import bots_auto


def mirror(bot, shape, games, seed=0, label="auto"):
    res = Counter()
    first_wins = 0
    decisive = 0
    plies = 0
    for g in range(games):
        rng = random.Random(hash((label, shape, seed, g)) & 0xFFFFFFFF)
        r = engine_auto.play_game_auto(bot, bot, shape, first=1, rng=rng)
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
    shapes = ["square", "line", "el", "zig", "pyramid"]

    print("== Auto-elephant B3 self-play (1000 games/shape) ==")
    print(f"{'shape':<9} {'decisive':>8} {'draws':>6} {'capped':>7} "
          f"{'1st-mover':>10} {'avg plies':>10}")
    for shape in shapes:
        r = mirror(bots_auto.tactician_auto, shape, 1000)
        print(f"{shape:<9} {r['decisive']:>8} {r['draws']:>6} {r['capped']:>7} "
              f"{r['p1_rate']:>10.1%} {r['avg_plies']:>10.1f}")

    print("\n== Stall test: no-repetition-penalty B3 self-play (300 games) ==")
    print(f"{'shape':<9} {'decisive':>8} {'draws':>6} {'capped':>7} "
          f"{'1st-mover':>10} {'avg plies':>10}")
    for shape in ["square", "line"]:
        r = mirror(bots_auto.stall_tactician_auto, shape, 300, label="stall")
        print(f"{shape:<9} {r['decisive']:>8} {r['draws']:>6} {r['capped']:>7} "
              f"{r['p1_rate']:>10.1%} {r['avg_plies']:>10.1f}")


if __name__ == "__main__":
    main()
