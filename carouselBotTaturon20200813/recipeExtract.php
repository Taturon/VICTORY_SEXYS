<?php

// 入力値を用いてDB検索し、idを配列に格納
$search = '%' . $message['text'] . '%';
require_once('dbConnect.php');
$sql = "SELECT category_name, category_id FROM rakuten_recipe WHERE category_name LIKE ?";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(1, $search, PDO::PARAM_STR);
$stmt->execute();
$ids = $stmt->fetchAll();

// 検索ヒット件数が多い場合の処理
// ヒット件数が13件(クイックリプライの上限)より多い場合
if (count($ids) > 13) {
	$rep = count($ids) . "件ヒットしました！";
	$rep .= "\n対応するレシピが多すぎます...";
	$rep .= "\n下記より選択されるか、";
	$rep .= "\nもう少し具体的に教えて下さい！";
	$reply['messages'][0]['text'] = $rep;
	shuffle($ids);
	$examples = array_rand($ids, 13);
	$ids = array_intersect_key($ids, $examples);
	foreach ($ids as $id) {
		$reply['messages'][0]['quickReply']['items'][] = [
			'type' => 'action',
			'action' => [
				'type' => 'message',
				'label' => $id['category_name'],
				'text' => $id['category_name']
			]
		];
	}

// ヒット件数が1件より多い場合
} elseif (count($ids) > 1) {
	$rep = count($ids) . "件ヒットしました！";
	$rep .= "\n下記より選択して下さい！";
	$reply['messages'][0]['text'] = $rep;
	foreach ($ids as $id) {
		$reply['messages'][0]['quickReply']['items'][] = [
			'type' => 'action',
			'action' => [
				'type' => 'message',
				'label' => $id['category_name'],
				'text' => $id['category_name']
			]
		];
	}

//ヒット件数が1件の場合
} elseif (count($ids) === 1) {

	// 取得したidを使ってレシピデータを配列に格納
	foreach ($ids as $key => $id) {
		$baseurl = 'https://app.rakuten.co.jp/services/api/Recipe/CategoryRanking/20170426';
		$params['applicationId'] = '1070228718341633238';
		$params['elements'] = 'recipeTitle,recipeDescription,recipeMaterial,foodImageUrl,recipeUrl';
		$params['categoryId'] = $id['category_id'];
		$canonical_string = '';
		foreach($params as $k => $v) {
			$canonical_string .= '&' . $k . '=' . $v;
		}
		$canonical_string = substr($canonical_string, 1);
		$url = $baseurl . '?' . $canonical_string;
		$recipes[$key] = json_decode(@file_get_contents($url, true), true);
	}

	// 得られた結果を繰り返し処理
	foreach ($recipes as $recipe) {

		// 各データの最上位にresult階層があるので更に繰り返し処理
		foreach ($recipe['result'] as $data) {

			// タイトルが40文字以上の場合はトリミング
			if (mb_strlen($data['recipeTitle']) > 40) {
				$str_t = str_replace(PHP_EOL, '', $data['recipeTitle']);
				$str_t = preg_split('//u', $str_t, 0, PREG_SPLIT_NO_EMPTY);
				$title = '';
				for ($i = 0; $i < 37; $i++) {
					    $title .= $str_t[$i];
				}
				$title .= '...';
			} else {
				$title = str_replace(PHP_EOL, '', $data['recipeTitle']);
			}

			// 説明が60文字以上の場合はトリミング
			if (mb_strlen($data['recipeDescription']) > 60) {
				$str_d = str_replace(PHP_EOL, '', $data['recipeDescription']);
				$str_d = preg_split('//u', $str_d, 0, PREG_SPLIT_NO_EMPTY);
				$description = '';
				for ($i = 0; $i < 57; $i++) {
					    $description .= $str_d[$i];
				}
				$description .= '...';
			} else {
				$description = str_replace(PHP_EOL, '', $data['recipeDescription']);
			}

			// カラムオブジェクトの生成
			$columns[] = [
				'thumbnailImageUrl' => $data['foodImageUrl'],
				'title'   => $title,
				'text'    => $description,
				'actions' => [
					[
						'type' => 'uri',
						'uri' => $data['recipeUrl'],
						'label' => '詳細はこちら>>'
					]
				]
			];
		}
	}

	// テンプレートオブジェクト及びカルーセルテンプレートの生成
	$template = ['type' => 'carousel', 'columns' => $columns];
	$reply['messages'][0] = ['type' => 'template', 'altText' => 'すみません...', 'template' => $template];
}
