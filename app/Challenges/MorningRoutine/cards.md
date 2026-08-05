these are the cards that i formerly used in the physical version of this game. i am rewriting them to adadpt them for this context. the idea is that each of these is an action you can take in one of the rooms. each has a point value, which is credited to the player. and each has a mess value which is debited to the room. so the next thing we should build here is that a player can do these actions in each room. and when they do they receive points, and the mess value in the room rises. when you're in the room, you can see its mess value. 

of course the long tail of complexity here is that many of these actions also has some effect. and these will need to hook into the game at places like GameEnded, PlayerLeftRoom, PlayerEnteredRoom, etc. And so let's follow the existing pattern in other challenges from other game modes on how to trigger these. this will take some work. perhaps propose which hooks we'll use, and what new events we need? i could see us needing playerqueuedformorningroutineroom (maybe don't need the name of the game), and playergotbusted

when showing the actions available, don't show the flavor. after they choose an action, show the flavor in a fun little animated slam like, "hey you successfully did the thing, and added this mess to the room -- here's your flavor."

once you've taken an action in a room, you cannot take any more actions there, AND the action you took is not available to any other players. the actions available in each room should be randomized so that it's equal to the number of players + 2


bathroom
header: "Freshen up" 
[
    {
        "Name": "Hand sanitizer",
        "Flavor": "Flatten the curve, and your opponents.",
        "Effect": "Immediately remove up to 5 ",
        "Points": "1"
        mess: 1
    },
    {
        "Name": "Hot shave",
        "Flavor": "Smooth as a baby's bottom.",
        "Points": 2
        mess: 2
    },
    {
        "Name": "Luxurious Shower",
        "Flavor": "Do not, my friends, become addicted to hot water. Because it all belongs to me.",
        "Points": 3
        mess: 3
    },
    {
        "Name": "Mirror",
        "Flavor": "Mirror, mirror, on the wall, who's worth the most points?",
        "Effect": "At the end of the game, double the point value of your lowest value reward.",
        "Points": "?"
        Mess: 0
    },
    {
        "Name": "Morning constitutional",
        "Flavor": "Even had time to do the crossword!",
        "Points": 2
        mess: 2
    },
    {
        "Name": "Tough morning",
        "Flavor": "Everything ok in there?",
        "Points": 1
        mess: 3
    },
    {
        "Name": "Unintentional cold plunge",
        "Flavor": "Who needs coffee when you've got this?",
        "Points": 1
        mess: 1
    }
]


kitchen
header: "Fuel up"
[
    {
        "Name": "Coffee",
        "Flavor": "The socially acceptable drug of choice.",
        "Effect": "You may collect 2 different rewards from the study.",
        "Points": 0
        mess: 2
    },
    {
        "Name": "Compost Bin",
        "Flavor": "One man's egg shell is another man's worm food.",
        "Effect": "At the end of the game, your negative mess penalties count as positive.",
        "Points": 0
        mess: 3
    },
    {
        "Name": "Energy Drink",
        "Flavor": "Here for a good time, not a long time.",
        "Effect": "After taking this, immediately clean this room.",
        "Points": 1
        Mess: 0
    },
    {
        "Name": "Enforcer's donut",
        "Flavor": "He's a cop now?",
        "Effect": "The next time you bust an opponent leaving a room with a mess, double their penalty.",
        "Points": 1
        mess: 1
    },
    {
        "Name": "Juice cleanse",
        "Flavor": "Cleanliness begins from within.",
        "Effect": "",
        "Points": 1
    },
    {
        "Name": "Junk drawer",
        "Flavor": "Sauce packets, dead batteries, and keys to nowhere.",
        "Effect": "Take a random kitchen reward that is not in this game",
        "Points": ?
        mess: ?
    },
    {
        "Name": "Molasses",
        "Flavor": "Careful what you wish for.",
        "Effect": "The next time an opponent busts you leaving a room with a mess, they will get covered in molasses: freeze for 30 seconds and gain 2 mess penalty.",
        "Points": 1
        mess: 3
    },
    {
        "Name": "Oatmeal",
        "Flavor": "A healthy breakfast that runs right through you.",
        "Effect": "You may collect 2 bathroom rewards.",
        "Points": "1"
        mess: 3
    },
]

laundry
header: dress for the job you want
[
    {
        "Name": "Boss suit",
        "Flavor": "Show them who wears the pinstripes around here.",
        "Effect": "Ignore the next time an opponent busts you, and take no penalty for your mess.",
        "Points": 1
        mess: 2
    },
    {
        "Name": "Janitor's uniform",
        "Flavor": "Clean up on aisle 6!",
        "Effect": "Every time you clean a room, gain a point.",
        "Points": "0"
        mess: 3
    },
        {
        "Name": "Holy robes",
        "Flavor": "Holier than thou, and thou, and thou.",
        "Effect": "The next time an opponent tries to bust you by queuing into your room, and there is no mess, gain 3 points.",
        "Points": "0"
        mess: 2
    },
    {
        "Name": "Lucky socks",
        "Flavor": "Luck is what happens when wealth meets nepotism.",
        "Effect": "The next time your enter the hallway, and there are opponents queued for other rooms, but not your room, gain 3 points.",
        "Points": "0"
        mess: 1
    },
    {
        "Name": "Librarian sweater",
        "Flavor": "cardigans never go out of style.",
        "Effect": "At the end of the game, if the study has 0 mess, you gain 3 points.",
        "Points": "0"
        mess: 1
    },
    {
        "Name": "Monocle",
        "Flavor": "second best thing to opera glasses.",
        "Effect": "When in the hallway, you can see the mess level of each room that has an open door.",
        "Points": 2
        mess: 1
    },
    {
        "Name": "Parachute pants",
        "Flavor": "Looking great is its own reward.",
        "Effect": "",
        "Points": 3
        mess: 3
    },
    {
        "Name": "White linen suit",
        "Flavor": "Spotless, and soft to the touch. Quite dashing.",
        "Effect": "Reveal at the end of the game. If you have 0 mess penalties, gain 3 points.
        "Points": "?"
        mess: 3
    }
]

study
header: cram before class

[
    {
        "Name": "Anarchist's Cookbook",
        "Flavor": "Cooking up something chaotic",
        "Effect": "At the end of the game, gain points equal to the mess level of the kitchen.",
        "Points": "?"
        mess: 3
    },
    {
        "Name": "Gambler's fallacy",
        "Flavor": "It's not a game of chance, it's a game of skill.",
        "Effect": "At the end of this game, this reward will either be worth 3 or -1.",
        "Points": "?"
        mess: 2
    },
    {
        "Name": "Housekeeping Handbook",
        "Flavor": "I want this place to shine like the top of the Chrysler Building!",
        "Effect": "At the end of the game, if the bathroom has 0 mess, gain 4 points",
        "Points": "0"
        mess: 2
    },
    {
        "Name": "Trap Doors For Dummies",
        "Flavor": "Not just for theater kids anymore.",
        "Effect": "You may move between unoccupied rooms without entering the hallway",
        "Points": 0
        mess: 1
    },
    {
        "Name": "Intermittent Fasting",
        "Flavor": "It's not a real diet unless you tell everyone about it.",
        "Effect": "At the end of the game, if you have 0 Kitchen cards, gain 3 points",
        "Points": "1"
        mess: 2
    }
]