<svg width="0" height="0" style="position:absolute">
  <defs>
    <!-- Backgrounds using actual images -->
    <symbol id="bg-grass" viewBox="0 0 1000 1000">
      <image href="/images/farm/grass.jpg" x="0" y="0" width="1000" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-desert" viewBox="0 0 1000 1000">
      <image href="/images/farm/desert.jpg" x="0" y="0" width="1000" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-mountain" viewBox="0 0 1000 1000">
      <image href="/images/farm/mountain.jpg" x="0" y="0" width="1000" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-swamp" viewBox="0 0 1000 1000">
      <image href="/images/farm/swamp.jpeg" x="0" y="0" width="1000" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>

    <!-- Objects (100x100 local coords for easy scaling) -->
    <symbol id="obj-farm" viewBox="0 0 100 100">
      <rect x="10" y="40" width="80" height="50" fill="#b23b3b"/>
      <polygon points="10,40 50,10 90,40" fill="#8d2f2f"/>
    </symbol>

    <symbol id="obj-silo" viewBox="0 0 100 100">
      <rect x="40" y="20" width="20" height="60" rx="8" fill="#9aa3ad"/>
      <circle cx="50" cy="20" r="12" fill="#7f8891"/>
    </symbol>

    <!-- Roads can be straight segments you rotate/scale -->
    <symbol id="obj-road" viewBox="0 0 100 100">
      <rect x="0" y="45" width="100" height="10" fill="#555"/>
      <rect x="0" y="49" width="100" height="2" fill="#bbb" />
    </symbol>

    <!-- Player pawn placeholder -->
    <symbol id="obj-player" viewBox="0 0 100 100">
      <circle cx="50" cy="35" r="15" />
      <rect x="35" y="50" width="30" height="35" rx="8" />
    </symbol>
  </defs>
</svg>
