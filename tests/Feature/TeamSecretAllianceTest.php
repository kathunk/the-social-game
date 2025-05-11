<?php



it('has teamsecretalliance page', function () {
    $response = $this->get('/teamsecretalliance');

    $response->assertStatus(200);
});

// it assigns an ally when a player discovers the page
// the correct text is rendered

// it shows that no valid players exists and doesn't assign one if not possible
// the correct text is rendered

// it rewards the team when the pair connects
// the correct text is rendered

// it does not give the players another reward later on