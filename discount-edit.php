<?php session_start(); ?>
<?php require 'header.php'; ?>
<?php require 'menu.php'; ?>
<?php

// エラーメッセージを空に
for ($i = 1; $i <= 9; $i++) {
	$_SESSION['errMsg' . $i] = '';
}

//URLパラメーターの値が正しければ、対象のデータを更新用入力項目にセットするSQL
if (isset($_REQUEST['dis_num']) && is_numeric($_REQUEST['dis_num'])) {
	$dis_num = $_REQUEST['dis_num'];

	$discount = $pdo->prepare('SELECT * FROM discount_detail WHERE kbn_id=? AND id=? AND dis_num=?');
	$discount->bindParam(1, $_SESSION['disresult']['kbn_id'], PDO::PARAM_INT);
	$discount->bindParam(2, $_SESSION['disresult']['id'], PDO::PARAM_INT);
	$discount->bindParam(3, $dis_num, PDO::PARAM_INT);
	$discount->execute();
	//データ読み込み
	$discounts = $discount->fetch();
}
//年月日の表示調整
$p1 = date('Y/m/d', strtotime($discounts['start_day']));
$p2 = date('Y/m/d', strtotime($discounts['end_day']));

?>

<!-- ボタンが押されたとき -->
<!-- var_dump($_POST['']); -->
<?php
$done = false; // 完了フラグ
// <!-- ボタンが押されたとき -->
if (isset($_POST['action'])) {
	$action = $_POST['action'];
	// ▼ 登録ボタン押したとき  
	if ($action == 'upd') {
		// 1区分が選択されているか確認
		if (!isset($_POST['kbn_id'])) {
			$_SESSION['errMsg1'] = '<span style="color:red;">区分が選択されていません</span>';
		}
		// 2割引IDの中身が空じゃないか確認
		if (!isset($_POST['id'])) {
			$_SESSION['errMsg2'] = '<span style="color:red;">割引項目が選択されていません</span>';
		}
		// 4割引方法が選択されているか確認
		if (!isset($_POST['price_flag'])) {
			$_SESSION['errMsg3'] = '<span style="color:red;">割引方法が選択されていません</span>';
		}
		// 開始日の中身が空じゃないか確認
		if (empty($_POST['start_date'])) {
			$_SESSION['errMsg4'] = '<span style="color:red;">開始日を選んでください</span>';
		}
		// 終了日の中身が空じゃないか確認
		if (empty($_POST['end_date'])) {
			$_SESSION['errMsg5'] = '<span style="color:red;">終了日を選んでください</span>';
		}
		// 6割引方法が選択されているか確認
		if (!isset($_POST['price_flag'])) {
			var_dump($_POST['price_flag']);
			$_SESSION['errMsg6'] = '<span style="color:red;">割引方法が選択されていません</span>';
		}
		// 7割引率（額）の確認
		// 空チェック
		if ($_POST['discount_price'] === '' || $_POST['discount_price'] === null) {
			$_SESSION['errMsg7'] = '<span style="color:red;">割引率が未入力です</span>';
		}
		// 数値チェック
		if (!is_numeric($_POST['discount_price'])) {
			$_SESSION['errMsg7'] = '<span style="color:red;">割引率は数値で指定してください</span>';
		}
		// マイナス値チェック
		if ($_POST['discount_price'] < 0) {
			$_SESSION['errMsg7'] = '<span style="color:orange;">割引率は０以上にしてください</span>';
		}

		// 8区分で’商品’が選択されている場合商品が選択されているか
		if ($_POST['kbn_id'] == 2 && empty($_POST['dis_item'])) {
			$_SESSION['errMsg8'] = '<span style="color:red;">商品が選択されていません</span>';
		}

		// エラーが空じゃないならエラー表示し、空ならdbへデータを書き込み
		if (!empty($_SESSION['errMsg1']) || !empty($_SESSION['errMsg2']) || !empty($_SESSION['errMsg3']) || !empty($_SESSION['errMsg4']) || !empty($_SESSION['errMsg5']) || !empty($_SESSION['errMsg6']) || !empty($_SESSION['errMsg7']) || !empty($_SESSION['errMsg8'])) {
			echo $_SESSION['errMsg1'] . $_SESSION['errMsg2'] . $_SESSION['errMsg3'] . $_SESSION['errMsg4'] . $_SESSION['errMsg5'] . $_SESSION['errMsg6'] . $_SESSION['errMsg7'] . $_SESSION['errMsg8'];
		} else {
			$updans = $pdo->prepare('UPDATE discount_detail SET kbn_id=?, id=?, dis_name=?, price_flag=?, discount_price=?, dis_item=?, start_day=?, end_day=? WHERE dis_num=?');

			$updans->bindParam(1, $_POST['kbn_id'], PDO::PARAM_INT);
			$updans->bindParam(2, $_POST['id'], PDO::PARAM_INT);
			$updans->bindParam(3, $_POST['dis_name'], PDO::PARAM_STR);
			$updans->bindParam(4, $_POST['price_flag'], PDO::PARAM_INT);
			$updans->bindParam(5, $_POST['discount_price'], PDO::PARAM_INT);
			$updans->bindParam(6, $_POST['dis_item'], PDO::PARAM_INT);
			$updans->bindParam(7, $_POST['start_date'], PDO::PARAM_STR);
			$updans->bindParam(8, $_POST['end_date'], PDO::PARAM_STR);
			$updans->bindParam(9, $_POST['dis_num'], PDO::PARAM_INT);
			$updans->execute();
			echo '更新が完了しました';
			echo '<a href="discount.php">一覧画面へ</a>';
			$done = true;
		}

		// ▼ 削除ボタン押したとき
	} elseif ($action == 'del') {
		$delans = $pdo->prepare('DELETE FROM discount_detail WHERE kbn_id=? AND id=? AND dis_num=?');
		$delans->bindParam(1, $_SESSION['disresult']['kbn_id'], PDO::PARAM_INT);
		$delans->bindParam(2, $_SESSION['disresult']['id'], PDO::PARAM_INT);
		$delans->bindParam(3, $dis_num, PDO::PARAM_INT);
		$delans->execute();
		echo '削除が完了しました';
		echo '<a href="discount.php"><button class="cart-button">一覧画面へ</button></a>';
		$done = true;
	}
}
?>

<!-- データ更新用の入力項目 -->
<!-- 対象のデータを入力項目にセット -->
<!-- 更新または削除が終わったらフォームを隠す -->
<div class="form-login">
	<?php if (!$done): ?>
		<form action="" method="post">
			<!-- //割引区分フォーム -->
			<table>
				<tr>
					<td>割引区分</td>
					<td>
						<input type="radio" name="kbn_id" value="1" <?php if ($discounts['kbn_id'] == 1) echo 'checked'; ?>>一会計
						<input type="radio" name="kbn_id" value="2" <?php if ($discounts['kbn_id'] == 2) echo 'checked'; ?>>商品
					</td>
				</tr>
				<tr>
					<td>割引項目</td>
					<td>
						<select name="id">
							<?php
							$d_is = [
								'日替わり' => 1,
								'記念日' => 2,
								'週間' => 3,
							];
							foreach ($d_is as $key => $d_i) {
								$selected = ($discounts['id'] == $d_i) ? 'selected' : '';
								echo '<option value="', h($d_i), '" ', $selected, '>', h($key), '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<!--  割引明細No -->
				<tr>
					<td>割引明細No</td>
					<td><input type="text" name="dis_num" value="<?php echo h($discounts['dis_num']); ?>" readonly></td>
				</tr>
				<!--  割引の名前 -->
				<tr>
					<td>割引名</td>
					<td><input type="text" name="dis_name" value="<?php echo h($discounts['dis_name']); ?>"></td>
				</tr>
				<!--  開始日入力カレンダ -->
				<tr>
					<td>開始日</td>
					<td>
						<input type="text" id="start_date" name="start_date" value="<?php echo h($p1); ?>" class="box">
					</td>
					<!-- 終了日入力カレンダ -->
					<td>終了日</td>
					<td>
						<input type="text" id="end_date" name="end_date" value="<?php echo h($p2); ?>" class="box">
					</td>
				</tr>
				<!-- //割引方法 -->
				<tr>
					<td>割引方法</td>
					<td>
						<input type="radio" name="price_flag" value="1" <?php if (h($discounts['price_flag']) == 1) echo 'checked'; ?>>％（率）
						<input type="radio" name="price_flag" value="2" <?php if (h($discounts['price_flag']) == 2) echo 'checked'; ?>>金額
					</td>
				</tr>
				<!--  割引率入力ボックス -->
				<tr>
					<td>割引率</td>
					<td>
						<input type="text" name="discount_price" value="<?php echo h($discounts['discount_price']); ?>">
					</td>
				</tr>
				<tr>
					<td>商品</td>
					<td>
						<!--  dbより商品のデータを読み込む -->
						<?php
						$current_item_id = $discounts['dis_item'];
						$product_id = $pdo->query('SELECT name, id FROM product ORDER BY id');
						$products_id = $product_id->fetchAll(PDO::FETCH_ASSOC);

						// 現在の商品名を見つける
						$current_product_name = '';
						foreach ($products_id as $p_i) {
							if ($p_i['id'] == $current_item_id) {
								$current_product_name = $p_i['name'];
								break;
							}
						}
						?>
						<!-- セレクトボックス（商品表示（選択されたもの) -->
						<select name="dis_item">
							<!-- ★ 先頭に現在の商品を表示 -->
							<?php if ($current_product_name !== ''): ?>
								<option value="<?php echo h($current_item_id); ?>">
									<?php echo h($current_product_name); ?>
								</option>
							<?php else: ?>
								<option value="">選択してください</option>
							<?php endif; ?>
							<!-- ★ 他の商品をすべて表示（現在のものは continue で除外） -->
							<?php foreach ($products_id as $p_i): ?>
								<?php if ($current_item_id == $p_i['id'])  continue; ?>
								<option value="<?php echo h($p_i['id']); ?>">
									<?php echo h($p_i['name']); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			<button type="submit" name="action" value="upd">更新</button>
			<button type="submit" name="action" value="del">削除</button>
			<a href="discount.php" class="return"><button type="button">戻る</button></a>
		</form>
	<?php endif; ?>
</div>
<?php require 'footer.php'; ?>