"""Validation of the research engine against the canonical rules.

Run: python3 test_engine.py
"""

import engine
from engine import (
    ALL_SLIDES, SHAPE_SPACES, bit, execute_slide, valid_slides,
    valid_elephant_moves, is_victorious, SHAPE_MASKS, hand,
)


def generate_shape_configs(cells):
    """All rotations + reflections of a polyomino, translated over the 4x4
    grid, as a set of frozensets of 1-16 spaces."""
    def normalize(pts):
        minr = min(r for r, c in pts)
        minc = min(c for r, c in pts)
        return frozenset((r - minr, c - minc) for r, c in pts)

    orientations = set()
    pts = frozenset(cells)
    for _ in range(4):
        pts = frozenset((c, -r) for r, c in pts)  # rotate 90
        orientations.add(normalize(pts))
        orientations.add(normalize(frozenset((r, -c) for r, c in pts)))  # mirror

    configs = set()
    for o in orientations:
        h = max(r for r, c in o) + 1
        w = max(c for r, c in o) + 1
        for dr in range(4 - h + 1):
            for dc in range(4 - w + 1):
                configs.add(frozenset((r + dr) * 4 + (c + dc) + 1 for r, c in o))
    return configs


def test_victory_lists_match_generated():
    base = {
        "square": [(0, 0), (0, 1), (1, 0), (1, 1)],
        "line": [(0, 0), (0, 1), (0, 2), (0, 3)],
        "el": [(0, 0), (1, 0), (2, 0), (2, 1)],
        "zig": [(0, 1), (0, 2), (1, 0), (1, 1)],
        "pyramid": [(0, 0), (0, 1), (0, 2), (1, 1)],
    }
    expected_counts = {"square": 9, "line": 8, "el": 48, "zig": 24, "pyramid": 24}
    for name, cells in base.items():
        generated = generate_shape_configs(cells)
        hardcoded = {frozenset(c) for c in SHAPE_SPACES[name]}
        assert len(hardcoded) == expected_counts[name], (name, len(hardcoded))
        assert generated == hardcoded, (
            name,
            sorted(map(sorted, generated - hardcoded)),
            sorted(map(sorted, hardcoded - generated)),
        )


def test_slide_into_empty_lane():
    p1, p2, pushed = execute_slide(0, 0, (1, "down"), 1)
    assert p1 == bit(1) and p2 == 0 and pushed is None


def test_cascade_contiguous_only():
    # Tiles at 1 and 9, gap at 5: sliding down at 1 pushes 1 -> 5, leaves 9.
    p1 = bit(1) | bit(9)
    np1, np2, pushed = execute_slide(p1, 0, (1, "down"), 2)
    assert np2 == bit(1)
    assert np1 == bit(5) | bit(9)
    assert pushed is None


def test_full_lane_push_off():
    # Column 1 full: 1,5 owned by p1; 9,13 owned by p2. Slide down at 1.
    p1 = bit(1) | bit(5)
    p2 = bit(9) | bit(13)
    np1, np2, pushed = execute_slide(p1, p2, (1, "down"), 1)
    assert pushed == 2  # tile at 13 fell off, owned by p2
    assert np1 == bit(1) | bit(5) | bit(9)
    assert np2 == bit(13)
    assert hand(np2) == 7  # 8 - 1 tile left on board


def test_elephant_blocks_entry():
    assert (1, "down") not in valid_slides(0, 1)
    assert (1, "right") not in valid_slides(0, 1)


def test_elephant_blocks_deeper_only_if_contiguous():
    # Elephant at 5 (position 2 of the (1, down) path).
    # Entry 1 empty -> slide fine (tile enters at 1, no cascade needed).
    assert (1, "down") in valid_slides(0, 5)
    # Entry 1 occupied -> tile at 1 must move into 5 -> blocked.
    assert (1, "down") not in valid_slides(bit(1), 5)
    # Elephant at 9 (position 3): blocked only when 1 AND 5 occupied.
    assert (1, "down") in valid_slides(bit(1), 9)
    assert (1, "down") not in valid_slides(bit(1) | bit(5), 9)


def test_elephant_never_blocks_more_than_four_slides():
    import random
    rng = random.Random(1)
    for _ in range(200):
        occ = rng.getrandbits(16)
        ele = rng.randint(1, 16)
        occ &= ~bit(ele) if rng.random() < 0.5 else 0xFFFF
        assert len(valid_slides(occ, ele)) >= 12


def test_elephant_moves_include_stay_and_occupied():
    assert set(valid_elephant_moves(6)) == {2, 5, 7, 10, 6}
    assert set(valid_elephant_moves(1)) == {2, 5, 1}


def test_victory_detection():
    masks = SHAPE_MASKS["square"]
    assert is_victorious(bit(6) | bit(7) | bit(10) | bit(11), masks)
    assert not is_victorious(bit(6) | bit(7) | bit(10) | bit(12), masks)
    line = SHAPE_MASKS["line"]
    assert is_victorious(bit(13) | bit(14) | bit(15) | bit(16), line)


def test_full_game_runs():
    import random
    import bots
    rng = random.Random(42)
    for shape in engine.ALL_SHAPES:
        for _ in range(5):
            result = engine.play_game(
                bots.random_bot, bots.random_bot, shape, rng=rng
            )
            assert result["result"] in ("win", "double", "draw", "capped")


if __name__ == "__main__":
    fns = [v for k, v in sorted(globals().items()) if k.startswith("test_")]
    for fn in fns:
        fn()
        print(f"ok  {fn.__name__}")
    print(f"\n{len(fns)} tests passed")
