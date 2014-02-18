<?php
/*
 *  Copyright (c) 2014 Marcin Łabanowski
 */

if (isset ($brd) && !defined("TINYBOARD")) {
  define("BOARDLINK_IN", "callback");
  require_once("inc/functions.php");
}
else if (defined ("TINYBOARD")) {
  define("BOARDLINK_IN", "config");
}
else {
  die("Access denied");
}

class BoardLink {
  function __construct($boardname, $self, $connected) {
    $this->boardname = $boardname;
    $this->self = $self;
    $this->connected = $connected;
  }

  function send_data($uri, $password, $data) {
    $d = http_build_query(array('password' => $password,
				   'from' => $this->self,
                                   'data' => serialize($data)));
    $ctx = stream_context_create(array('http' => array(
				         'method' => 'POST',
                                         'header' => "Content-type: application/x-www-form-urlencoded\r\n".
				                     "Content-length: ".strlen($d)."\r\n",
					 'content' => $d)));
    $fp = file_get_contents($uri.'callback.php', false, $ctx);

    _syslog(LOG_INFO, "BoardLink: sent query of type {$data['action']} from {$this->self} to $uri. Query yielded $fp");
    return $fp;
  }

  function configure_board() {
    global $config;

    $config['blotter'] = "This board is synchronized with ";
    foreach ($this->connected as $boardurl) {
      $config['blotter'] .= "<a href='$boardurl'>$boardurl</a> and ";
    }
    $config['blotter'] = preg_replace('/ and $/', '', $config['blotter']);

    if ($config['vichan_federation']) {
      $config['locale'] = "en_US.UTF-8";
      $config['blotter'] .= '. Please visit <a href="https://int.vichan.net/*/">https://int.vichan.net/*/</a>'.
				' to get a feed of all /int/s from VICHAN Federation.';
      $config['country_flags'] = true;
    }

    event_handler('delete', function($post) {
      $data = array();
      $data['action'] = 'delete';
      $data['post'] = $post;
      foreach ($this->connected as $password => $uri) {
	$this->send_data($uri, $password, $data);
      }
    });

    event_handler('post-after', function($post) {
      $data = array();
      $data['action'] = 'create';
      if (!isset ($post['ip'])) $post['ip'] = $_SERVER['REMOTE_ADDR'];
      $data['post'] = $post;
      foreach ($this->connected as $password => $uri) {
	if (!isset($data['post']['origin']) || $data['post']['origin'] != $uri) {
          $this->send_data($uri, $password, $data);
	}
      }
    });
  }

  function handle_error($err, $from, $sort) {
    _syslog(LOG_INFO, "BoardLink: received query of type $sort from $from to {$this->self}. Query finished with $err");
    die('{status:"'.$err.'"}');
  }

  function configure_callback() {
    global $board, $config;

    if (!isset ($this->connected[$_POST['password']])) {
      $this->handle_error("ERR_PASSWD", $_POST['from'], "unk");
    }
    $uri = $this->connected[$_POST['password']];
    if ($uri != $_POST['from']) {
      $this->handle_error("ERR_FROM", $_POST['from'], "unk");
    }
    $data = unserialize($_POST['data']);

    openBoard($this->boardname);

    switch ($data['action']) {
      case 'create':
        //Check if thread exists
        if (!$data['post']['op']) {
                $query = prepare(sprintf("SELECT `sticky`,`locked`,`sage` FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL LIMIT 1", $board['uri']));
                $query->bindValue(':id', $data['post']['thread'], PDO::PARAM_INT);
                $query->execute() or error(db_error());

                if (!$thread = $query->fetch(PDO::FETCH_ASSOC)) {
			$this->handle_error("ERR_DESYNC", $uri, $data['action']);
                }

                $numposts = numPosts($data['post']['thread']);
        }

        $a = array("src" => "file", "thumb" => "thumb");
        foreach ($a as $dir => $field) {
          if (isset($data['post'][$field]) && $data['post'][$field] &&
              $data['post'][$field] != 'spoiler' && $data['post'][$field] != 'deleted') {
	    // Security filename checks
	    if (preg_match('@\.php|\.phtml|\.ht|\.\.|\x00|/@i', $data['post'][$field])) {
	      $this->handle_error("ERR_SECURITY", $uri, $data['action']);
	    }
            $i = file_get_contents($uri.$dir.'/'.$data['post'][$field]);
            file_put_contents($this->boardname.'/'.$dir.'/'.$data['post'][$field], $i);
          }
        }
	$tmpid = post($data['post']);

	// Post doesn't cover custom post IDs
	$query = prepare(sprintf("UPDATE ``posts_%s`` SET `id`=:id WHERE `id`=:tmpid", $board['uri']));
	$query->bindValue("id", $id = $data['post']['id']);
	$query->bindValue("tmpid", $tmpid);
	$query->execute();

	// Reset the auto increment
	query(sprintf("ALTER TABLE ``posts_%s`` AUTO_INCREMENT = 1", $board['uri']));

	$post = &$data['post'];
	$post['origin'] = $uri;

	// The rest is just a copied code from post.php

        if (isset($post['tracked_cites']) && !empty($post['tracked_cites'])) {
                $insert_rows = array();
                foreach ($post['tracked_cites'] as $cite) {
                        $insert_rows[] = '(' .
                                $pdo->quote($board['uri']) . ', ' . (int)$id . ', ' .
                                $pdo->quote($cite[0]) . ', ' . (int)$cite[1] . ')';
                }
                query('INSERT INTO ``cites`` VALUES ' . implode(', ', $insert_rows)) or error(db_error());
        }

        if (!$post['op'] && strtolower($post['email']) != 'sage' && !$thread['sage'] && ($config['reply_limit'] == 0
			|| $numposts['replies']+1 < $config['reply_limit'])) {
                bumpThread($post['thread']);
        }

        buildThread($post['op'] ? $id : $post['thread']);
        
        if ($config['try_smarter'] && $post['op'])
                $build_pages = range(1, $config['max_pages']);

        if ($post['op'])
                clean();
                
        event('post-after', $post);
        
        buildIndex();

        break;

      case 'delete':
	deletePost($data['post']['id'], false);

        break;
    }

    $this->handle_error("OK", $uri, $data['action']);
  }
}
?>
