# Game States

Visual Diagram (hand made), in order to keep a trace of each possible transition

                    SETUP
                    |
                    v
                    newSession (used for Campaign) <------------\
                    |                                           |
                    v                                           |
                    newRound   <-----------------------------\  |
                    |                                        |  |
                    v                                        |  |
                    newTurn                                  |  |
                    |                                        |  |
                    v                                        |  |
                    nextPlayer  <---------------------\      |  |
                    |                                 ^      |  |
                    v                                 |      |  |
               playerTurn   -------\                  |      |  |
              |       |            |                  |      |  |
              |       v            |                  |      |  |
              |playerChoice->\     |                  |      |  |
              |       |      |     |                  |      |  |
              v       v      |     |                  |      |  |
              YYYYYYYYY  -->\|     |                  |      |  |
                            ||     |                  |      |  |
                            vv     v                  |      |  |
                            confirm --> endPlayTurn ->/      |  |
                                           |                 |  |
                                           |                 |  |
                                           v                 |  |
                                           endRound -------->/  |
                                           |                    |
                                           v                    |
                                           endSession  -------->/
                                                |
                                                v
                                                EndScore
                                                | 
                                                v
                                                END