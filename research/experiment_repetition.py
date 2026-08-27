"""Pressure-test repetition-loss rule proposals.

Question: John proposed "3 consecutive identical moves = you LOSE".
Variants under test (both implemented in engine.play_game):
  move3 — same slide three times in a row by one player = loss
  pos3  — recreating the same position (tiles + elephant + to-move) a
          third time = loss for the player who recreates it

For each rule we test:
  1. A motivated staller (tactician with its repetition penalty disabled,
     i.e. happy to fortress forever) vs a guard bot — does the rule actually
     force games to end? Stallers are made "rule-aware": if their chosen
     move would lose by rule, they substitute a legal non-losing move.
  2. Rule-UNaware bots (the shipped-bot tier) — how often does the rule
     ambush a player who isn't thinking about it?

Usage: python3 experiment_repetition.py
"""

import random
from collections import Counter

import engine
from engine import (
    execute_slide, next_turn_after, valid_elephant_moves, valid_slides,
)
import bots


def stall_tactician(p1, p2, elephant, mover, masks, rng, history=None):
    """Tactician with no repetition penalty: will happily fortress forever."""
    return bots.tactician_bot(p1, p2, elephant, mover, masks, rng, None)


def rule_aware(bot, rule):
    """If the bot's chosen move would lose by the rule, substitute the
    cheapest legal alternative (random non-losing move)."""

    def wrapped(p1, p2, elephant, mover, masks, rng, history=None):
        h = history or {}
        slide, dest = bot(p1, p2, elephant, mover, masks, rng, history)

        if rule and rule.startswith("move"):
            n = int(rule[4:])
            last = h.get("_moves", {}).get(mover, [])
            if (len(last) >= n - 1
                    and all(m == slide for m in last[-(n - 1):])):
                alts = [s for s in valid_slides(p1 | p2, elephant) if s != slide]
                slide = rng.choice(alts)
        elif rule == "pos3":
            def trips(s, d):
                np1, np2, _ = execute_slide(p1, p2, s, mover)
                nxt = next_turn_after(np1, np2, mover)
                return h.get((np1, np2, d, nxt), 0) >= 2

            if trips(slide, dest):
                options = [
                    (s, d)
                    for s in valid_slides(p1 | p2, elephant)
                    for d in valid_elephant_moves(elephant)
                    if not trips(s, d)
                ]
                if options:
                    slide, dest = rng.choice(options)
        return slide, dest

    return wrapped


def run(label, bot1, bot2, shape, rule, games=150):
    results = Counter()
    rep_losses = Counter()
    total_plies = 0
    for g in range(games):
        rng = random.Random(hash((label, shape, rule, g)) & 0xFFFFFFFF)
        r = engine.play_game(bot1, bot2, shape, first=1 + g % 2, rng=rng,
                             repetition_rule=rule)
        results[r["result"]] += 1
        total_plies += r["plies"]
        if r["result"] == "repetition":
            rep_losses[r["loser"]] += 1
    return results, rep_losses, total_plies / games


def main():
    shapes = ["square", "line", "zig", "pyramid"]
    rules = ["move3", "move4", "pos3"]
    staller_aware = {r: rule_aware(stall_tactician, r) for r in rules}
    guard_aware = {r: rule_aware(bots.guard_bot, r) for r in rules}

    print("== 1) Motivated staller (no-rep-penalty tactician) vs guard bot ==")
    print("   Does each rule actually force games to end?\n")
    header = f"{'shape':<8} {'rule':<12} {'decided':>7} {'draw':>5} {'capped':>7} {'rep-loss':>9} {'avg plies':>10}"
    print(header)
    for shape in shapes:
        for rule, staller, guard in [
            (None, stall_tactician, bots.guard_bot),
        ] + [(r, staller_aware[r], guard_aware[r]) for r in rules]:
            res, rep, avg = run("stall", staller, guard, shape, rule)
            decided = res["win"] + res["double"]
            print(f"{shape:<8} {str(rule):<12} {decided:>7} {res['draw']:>5} "
                  f"{res['capped']:>7} {res['repetition']:>9} {avg:>10.1f}")
        print()

    print("== 2) Rule-UNaware shipped-tier bots (B1 vs B2): ambush rate ==\n")
    print(header)
    for shape in shapes:
        for rule in rules:
            res, rep, avg = run("unaware", bots.current_bot, bots.guard_bot,
                                shape, rule)
            decided = res["win"] + res["double"]
            print(f"{shape:<8} {str(rule):<12} {decided:>7} {res['draw']:>5} "
                  f"{res['capped']:>7} {res['repetition']:>9} {avg:>10.1f}")
        print()


def run_length_collateral(shapes, games=150):
    """In healthy (rule-less, decisive) strong self-play, how often does a
    player naturally slide the same lane 3+ / 4+ times in a row?"""
    from engine import SHAPE_MASKS, hand, is_victorious

    print("== 3) Collateral: same-slide runs in healthy strong games ==\n")
    print(f"{'shape':<8} {'games':>6} {'run>=3':>8} {'run>=4':>8} {'run>=5':>8}")
    for shape in shapes:
        counts = Counter()
        total = 0
        for g in range(games):
            rng = random.Random(hash(("legit", shape, g)) & 0xFFFFFFFF)
            masks = SHAPE_MASKS[shape]
            p1 = p2 = 0
            elephant = 6
            turn = 1 + g % 2
            history = {"_moves": {1: [], 2: []}}
            max_run = 0
            run = {1: 0, 2: 0}
            prev = {1: None, 2: None}
            decided = False
            for _ply in range(200):
                key = (p1, p2, elephant, turn)
                history[key] = history.get(key, 0) + 1
                slide, dest = bots.tactician_bot(
                    p1, p2, elephant, turn, masks, rng, history)
                history["_moves"][turn].append(slide)
                run[turn] = run[turn] + 1 if slide == prev[turn] else 1
                prev[turn] = slide
                max_run = max(max_run, run[turn])
                p1, p2, _ = execute_slide(p1, p2, slide, turn)
                if is_victorious(p1, masks) or is_victorious(p2, masks):
                    decided = True
                    break
                if hand(p1) == 0 and hand(p2) == 0:
                    decided = True
                    break
                elephant = dest
                turn = next_turn_after(p1, p2, turn)
            if not decided:
                continue  # capped/stalled games are not "healthy"
            total += 1
            for n in (3, 4, 5):
                if max_run >= n:
                    counts[n] += 1
        print(f"{shape:<8} {total:>6} "
              f"{counts[3] / total:>8.0%} {counts[4] / total:>8.0%} "
              f"{counts[5] / total:>8.0%}")


if __name__ == "__main__":
    main()
    run_length_collateral(["square", "line", "zig", "pyramid"])
