<?php

require_once('line_bot_tiny.php');
require_once('.env.php');

$client = new LINEBotTiny(CHANNEL_ACCESS_TOKEN, CHANNEL_SECRET);
foreach ($client->parseEvents() as $event) {
	switch ($event['type']) {
		case 'follow':
			require_once('follow.php');
			break;
		case 'unfollow':
			require_once('unfollow.php');
			break;
		case 'message':
			$message = $event['message'];
			switch ($message['type']) {
				case 'location':
					$reply['replyToken'] = $event['replyToken'];
					require_once('restaurant_search/curl.php');
					$client->replyMessage($reply);
					break;
				case 'text':
					$reply['replyToken'] = $event['replyToken'];
					$reply['messages'][] = ['type' => 'text', 'text' => $message['text']];
					require_once('conditional_branch.php');
					$client->replyMessage($reply);
					break;
				default:
					error_log('Unsupported message type: ' . $message['type']);
					break;
			}
			break;
		default:
			error_log('Unsupported event type: ' . $event['type']);
			break;
	}
};
