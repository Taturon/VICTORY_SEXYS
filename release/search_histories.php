<?php

require_once('db_connect.php');

$line_id = $event['source']['userId'];
$sql = 'SELECT `category_name` from `histories` WHERE `line_id` = ? ORDER BY `id` DESC LIMIT 10';
$stmt = $dbh->prepare($sql);
$stmt->execute([$line_id]);
$histories = $stmt->fetchAll();

$reply['messages'][0]['text'] = '直近10件の検索履歴です!';
foreach ($histories as $history) {
	$reply['messages'][0]['quickReply']['items'][] = [
		'type' => 'action',
		'action' => [
			'type' => 'message',
			'label' => $history['category_name'],
			'text' => $history['category_name']
		]
	];
}