<svg width="0" height="0" style="position:absolute">
  <defs>
    <!-- Backgrounds using actual images -->
    <symbol id="bg-grass" viewBox="0 0 1600 1000">
      <image href="/images/farm/grass.jpg" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-desert" viewBox="0 0 1600 1000">
      <image href="/images/farm/desert.jpg" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-mountain" viewBox="0 0 1600 1000">
      <image href="/images/farm/mountain.jpg" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-swamp" viewBox="0 0 1600 1000">
      <image href="/images/farm/swamp.jpeg" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-ash_heap" viewBox="0 0 1600 1000">
      <image href="/images/farm/ash_heap.jpeg" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-fertile_ashland" viewBox="0 0 1600 1000">
      <image href="/images/farm/fertile_ashland.jpg" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-volcano" viewBox="0 0 1600 1000">
      <image href="/images/farm/volcano.jpg" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>
    <symbol id="bg-tunnel" viewBox="0 0 1600 1000">
      <image href="/images/farm/tunnel.png" x="0" y="0" width="1600" height="1000" preserveAspectRatio="xMidYMid slice"/>
    </symbol>

    <!-- Objects (100x100 local coords for easy scaling) -->

    <symbol id="obj-field-seedlings" viewBox="0 0 880 880">
      @include('components.game-components.custom-form-elements.farm-space-elements.field')
    </symbol>

    <symbol id="obj-field-sprouts" viewBox="0 0 880 880">
      @include('components.game-components.custom-form-elements.farm-space-elements.field')
    </symbol>

    <symbol id="obj-field-mature" viewBox="0 0 880 880">
      @include('components.game-components.custom-form-elements.farm-space-elements.field')
    </symbol>

    <symbol id="obj-field-mature_1" viewBox="0 0 880 880">
      @include('components.game-components.custom-form-elements.farm-space-elements.field')
    </symbol>

    <symbol id="obj-field-mature_2" viewBox="0 0 880 880">
      @include('components.game-components.custom-form-elements.farm-space-elements.field')
    </symbol>

    <symbol id="obj-field-mature_3" viewBox="0 0 880 880">
      @include('components.game-components.custom-form-elements.farm-space-elements.field')
    </symbol>

    <symbol id="obj-field-rotted" viewBox="0 0 880 880">
      @include('components.game-components.custom-form-elements.farm-space-elements.field')
    </symbol>

    <symbol id="obj-silo" viewBox="0 0 100 100">
      <!-- Red barn -->
      <rect x="10" y="50" width="25" height="30" fill="#c41e3a" stroke="#8b1a2e" stroke-width="1.5"/>
      <polygon points="10,50 22.5,35 35,50" fill="#8b1a2e" stroke="#6b1423" stroke-width="1.5"/>
      <!-- Barn door -->
      <rect x="17" y="65" width="10" height="15" fill="#5a1118"/>

      <!-- Brown silo -->
      <rect x="45" y="25" width="18" height="55" rx="6" fill="#8b6914" stroke="#5a4409" stroke-width="1.5"/>
      <!-- Silo top dome -->
      <ellipse cx="54" cy="25" rx="9" ry="6" fill="#6b5010" stroke="#5a4409" stroke-width="1.5"/>
      <!-- Silo bands for detail -->
      <line x1="45" y1="40" x2="63" y2="40" stroke="#5a4409" stroke-width="1"/>
      <line x1="45" y1="55" x2="63" y2="55" stroke="#5a4409" stroke-width="1"/>
      <line x1="45" y1="70" x2="63" y2="70" stroke="#5a4409" stroke-width="1"/>

      <!-- Connecting chute from barn to silo -->
      <rect x="35" y="54" width="10" height="4" fill="#6b5010" stroke="#5a4409" stroke-width="1"/>
    </symbol>

    <!-- Roads - horizontal road that spans the width -->
    <symbol id="obj-road" viewBox="0 0 100 100">
      <!-- Dark gray road base -->
      <rect x="0" y="40" width="100" height="20" fill="#555"/>
      <!-- Yellow center lines -->
      <rect x="0" y="48" width="100" height="4" fill="#ffcc00"/>
      <!-- Side edges -->
      <rect x="0" y="40" width="100" height="2" fill="#333"/>
      <rect x="0" y="58" width="100" height="2" fill="#333"/>
    </symbol>

    <!-- Player pawn placeholder -->
    <symbol id="obj-player" viewBox="0 0 100 100">
      <circle cx="50" cy="35" r="15" />
      <rect x="35" y="50" width="30" height="35" rx="8" />
    </symbol>
  </defs>
</svg>
