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

    <!-- Field stages - compact, with level indicators -->
    <!-- Note: Level number will be added dynamically via overlay config -->
    <symbol id="obj-field-seedlings" viewBox="0 0 100 100">
      <!-- Light green soil with tiny seedling dots -->
      <rect x="30" y="70" width="40" height="30" fill="#8b7355" rx="2" stroke="#654321" stroke-width="1"/>
      <circle cx="40" cy="85" r="2" fill="#90EE90"/>
      <circle cx="50" cy="85" r="2" fill="#90EE90"/>
      <circle cx="60" cy="85" r="2" fill="#90EE90"/>
      <!-- Level badge placeholder - will be filled with text overlay -->
      <circle cx="65" cy="73" r="5" fill="#fff" stroke="#654321" stroke-width="1"/>
    </symbol>

    <symbol id="obj-field-sprouts" viewBox="0 0 100 100">
      <!-- Medium green sprouts emerging -->
      <rect x="30" y="70" width="40" height="30" fill="#8b7355" rx="2" stroke="#654321" stroke-width="1"/>
      <line x1="40" y1="85" x2="40" y2="72" stroke="#3CB371" stroke-width="2"/>
      <line x1="50" y1="85" x2="50" y2="72" stroke="#3CB371" stroke-width="2"/>
      <line x1="60" y1="85" x2="60" y2="72" stroke="#3CB371" stroke-width="2"/>
      <!-- Level badge -->
      <circle cx="65" cy="73" r="5" fill="#fff" stroke="#654321" stroke-width="1"/>
    </symbol>

    <symbol id="obj-field-mature" viewBox="0 0 100 100">
      <!-- Full green mature crops -->
      <rect x="30" y="70" width="40" height="30" fill="#8b7355" rx="2" stroke="#654321" stroke-width="1"/>
      <rect x="37" y="60" width="6" height="25" fill="#228B22" rx="1"/>
      <rect x="47" y="55" width="6" height="30" fill="#228B22" rx="1"/>
      <rect x="57" y="58" width="6" height="27" fill="#228B22" rx="1"/>
      <!-- Level badge -->
      <circle cx="65" cy="73" r="5" fill="#fff" stroke="#654321" stroke-width="1"/>
    </symbol>

    <symbol id="obj-field-rotted" viewBox="0 0 100 100">
      <!-- Brown/dark rotted field -->
      <rect x="30" y="70" width="40" height="30" fill="#654321" rx="2" stroke="#3d2817" stroke-width="1"/>
      <path d="M 40 80 Q 40 77 43 77" stroke="#8B4513" stroke-width="2" fill="none"/>
      <path d="M 50 82 Q 50 79 53 79" stroke="#8B4513" stroke-width="2" fill="none"/>
      <path d="M 60 81 Q 60 78 63 78" stroke="#8B4513" stroke-width="2" fill="none"/>
      <!-- Level badge -->
      <circle cx="65" cy="73" r="5" fill="#fff" stroke="#654321" stroke-width="1"/>
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
