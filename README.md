Tinyboard-BoardLink
===================

Tinyboard-BoardLink is a board synchronization plugin for Tinyboard. It
allows you to have boards, that are synchronized in real time between
different installations.

At the moment only "create post" and "delete post" actions are synchronized.


Usage
-----
Name this directory ```boardlink``` and put it in the root directory of your
Tinyboard installation.

Then, copy files from ```example_config/``` to your board's directory.
Afterwards, edit your ```config.php``` file.

In the ```config.php``` file, the first string parameter is your board
location. So, eg. if your board is ```http://example.com/b/``` you should
set that to exactly that value. Use https instead of the http, if your
webserver is configured properly.

Think about the password. It can be a bunch of random characters. Give that
password to the other party, along with your board url you set up in the
previous step. This is important, that this string is the same on both parts,
this is your login. The password should be the same on both ends.

Be aware, that when your password gets leaked, the third party will be able
to remove your posts and inject raw html.

Before launching the boards to the public, make sure, that all board mirrors
have the same content (or are null). Otherwise, synchronization will work,
but post id clashes will happen very often.


Slow execution considerations
-----------------------------
We highly recommend our users to use php-fpm instead of the regular FastCGI
process manager. The plugin will work nevertheless, but using php-fpm allows
us to do use a fastcgi_finish_request() function, so that the synchronization
occurs in the background.

Actually, this is very important for big hubs, or else the posting on every
synced board will take so much precious time.


Spanning trees
--------------
You can synchronize as many boards as you'd like, but you must avoid circular
links. Basically, you must construct a tree, not a graph. Eg. this is ok:

              ,--- board2    ,--- board5
    board1 ---+--- board3 ---+--- board6
              `--- board4    `--- board7

This is not:

                   ,--- board2    ,--- board5 ---.
    ,--- board1 ---+--- board3 ---+--- board6    | 
    |              `--- board4    `--- board7    |
    `--------------------------------------------'

This isn't a restriction. It's a conscious design choice. IRC Networks work
this way and it just implies, that the farthest nodes have the biggest lag
between themselves.


Compatibility
-------------
This code should basically work on every Tinyboard instance but, some
events may not be propagated, or another issues may arise if you have an
outdated Tinyboard version. You can cherry-pick missing commits yourself
using ```git fetch http://github.com/vichan-devel/Tinyboard.git``` and
then ```git cherry-pick e7f25aa480```. You can also apply those changes
by hand by visiting: ```https://github.com/vichan-devel/Tinyboard/commit/e7f25aa480```.
The Tinyboard version listed is the one that is certain to have that code
included.

| commit id  | tinyboard version   | description                   |
| ---------- | ------------------- | ----------------------------- |
| e7f25aa480 | v0.9.6-dev-12       | Delete support                |
| cbf44d4d75 | vichan-devel-4.4.95 | Fix potential error on delete |


Support
-------
You may get support for this project on #vichan-federation channel on
6IRC.Net IRC Network.

Webchat: http://webchat.6irc.net/?channels=vichan-federation
