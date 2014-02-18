Tinyboard-BoardLink
===================

Tinyboard-BoardLink is a board synchronization plugin for Tinyboard. It
allows you to have boards, that are synchronized in real time between
different installations.

At the moment only "create post" and "delete post" actions are synchronized.

Usage
-----
Name this directory "boardlink" and put it in the root directory of your
Tinyboard installation.

Then, copy files from example_config/ to your board's directory. 

Spanning trees
--------------
You can synchronize as many boards as you'd like, but you must avoid circular
links. Basically, you must construct a tree, not a graph. Eg.:

            ,--- board2  ,--- board5
    board1 -+--- board3 -+--- board6
            `--- board4  `--- board7

Support
-------
You may get support for this project on #vichan-federation channel on
6IRC.Net IRC Network.

Webchat: http://webchat.6irc.net/?channels=vichan-federation
