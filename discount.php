<?php session_start(); ?>
<?php require 'header.php'; ?>
<?php require 'menu.php'; ?>
<?php

// エラーメッセージを空に
for ($i = 1; $i <= 9; $i++) {
  $_SESSION['errMsg' . $i] = '';
}

$done = false; // 完了フラグ
?>

<?php
// <!-- ボタンが押されたとき -->
if (isset($_POST['action'])) {
  $action = $_POST['action'];
  // ▼ 登録ボタン押したとき  
  if ($action == 'insert') {
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
      $_SESSION['errMsg4'] = '<span style="color:red;">割引方法が選択されていません</span>';
    }
    // 開始日の中身が空じゃないか確認
    if (empty($_POST['start_day'])) {
      $_SESSION['errMsg3'] = '<span style="color:red;">開始日を選んでください</span>';
    }
    // 6割引方法が選択されているか確認
    if (!isset($_POST['price_flag'])) {
      var_dump($_POST['price_flag']);
      $_SESSION['errMsg5'] = '<span style="color:red;">割引方法が選択されていません</span>';
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
    // 同じ割引（同じ区分・同じ日付）が二重登録されていないか確認
    $check = $pdo->prepare('SELECT COUNT(*) FROM discount_detail WHERE kbn_id=? AND id=? AND start_day=?');
    $check->execute([$_POST['kbn_id'], $_POST['id'], $_POST['start_day']]);
    if ($check->fetchColumn() > 0) {
      $_SESSION['errMsg9'] = '<span style="color:red;">同じ割引が既に登録されています</span>';
    }

    // エラーが空じゃないならエラー表示し、空ならdbへデータを書き込み
    if (!empty($_SESSION['errMsg1']) || !empty($_SESSION['errMsg2']) || !empty($_SESSION['errMsg3']) || !empty($_SESSION['errMsg4']) || !empty($_SESSION['errMsg5']) || !empty($_SESSION['errMsg6']) || !empty($_SESSION['errMsg7']) || !empty($_SESSION['errMsg8']) || !empty($_SESSION['errMsg9'])) {
      echo $_SESSION['errMsg1'] . $_SESSION['errMsg2'] . $_SESSION['errMsg3'] . $_SESSION['errMsg4'] . $_SESSION['errMsg5'] . $_SESSION['errMsg6'] . $_SESSION['errMsg7'] . $_SESSION['errMsg8'] . $_SESSION['errMsg9'];
    } else {

      // 終了日の設定
      // 割引項目１・２（日替わり・記念日の時）終了日は当日に
      if (($_POST['id'] == 1) || ($_POST['id'] == 2)) {
        $end_date = $_POST['start_day'];
        // 割引項目3（週間の時）終了日は1W後に
      } elseif ($_POST['id'] == 3) {
        $end_date = date(date("Y-m-d", strtotime('+7 days', strtotime($_POST['start_day']))));
        // それ以外は2099-12-31に
      } else {
        $end_date =  "2099-12-31";
      };

      // discount_detailテーブルから最大の割引明細Noを取得
      $stmt = $pdo->prepare('SELECT count(dis_num) AS max_dis_num FROM discount_detail WHERE kbn_id=? AND id=?');
      $stmt->bindParam(1, $_POST['kbn_id'], PDO::PARAM_INT);
      $stmt->bindParam(2, $_POST['id'], PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      $next_dis_num = $row['max_dis_num'];

      $insans = $pdo->prepare('INSERT INTO discount_detail SET kbn_id=?, id=?, dis_num=?, dis_name=?, price_flag=?, discount_price=?, dis_item=?, start_day=?, end_day=?');
      $insans->bindParam(1, $_POST['kbn_id'], PDO::PARAM_INT);
      $insans->bindParam(2, $_POST['id'], PDO::PARAM_INT);
      $insans->bindParam(3, $next_dis_num, PDO::PARAM_INT);
      $insans->bindParam(4, $_POST['dis_name'], PDO::PARAM_STR);
      $insans->bindParam(5, $_POST['price_flag'], PDO::PARAM_INT);
      $insans->bindParam(6, $_POST['discount_price'], PDO::PARAM_INT);
      $insans->bindParam(7, $_POST['dis_item'], PDO::PARAM_INT);
      $insans->bindParam(8, $_POST['start_day'], PDO::PARAM_STR);
      $insans->bindParam(9, $end_date, PDO::PARAM_STR);
      $insans->execute();
      echo '<p>登録が完了しました。</p>';
      echo '<a href="discount.php"><button class="cart-button">一覧画面へ</button></a>';
      $done = true;
    }
?>

    <!-- // ▼ 検索ボタン押したとき -->
<?php
  } elseif ($action == 'search') {
    echo '<div class="form-discount-result">';
    // 初期値セット
    $message = "";
    $items = [];

    $kbn_id = $_POST['kbn_id'];
    $id = $_POST['id'];
    $dis_item = $_POST['dis_item'];
    $start_date = $_POST['start_day'];

    // ▼ 割引区分・割引項目がない → エラー表示して終了
    if (!isset($kbn_id) || empty($id)) {
      echo '<p style="color:red;">割引区分・割引項目が選択されていません</p>';
      return;  // ★ここで処理終了
    }
    // ▼ ここから検索開始

    // ① 商品・開始日がない場合
    if (empty($dis_item) && empty($start_date)) {
      $discount = $pdo->prepare('SELECT * FROM discount_detail WHERE kbn_id=? AND id=? ORDER BY dis_num');
      $discount->bindParam(1, $kbn_id, PDO::PARAM_INT);
      $discount->bindParam(2, $id, PDO::PARAM_INT);

      // ② 割引区分・割引項目・商品を入力
    } elseif (!empty($dis_item) && empty($start_date)) {
      $discount = $pdo->prepare('SELECT * FROM discount_detail WHERE kbn_id=? AND id=? AND dis_item=? ORDER BY dis_num');
      $discount->bindParam(1, $kbn_id, PDO::PARAM_INT);
      $discount->bindParam(2, $id, PDO::PARAM_INT);
      $discount->bindParam(3, $dis_item, PDO::PARAM_INT);

      // ③ 割引区分・割引項目・開始日を入力
    } elseif (empty($dis_item) && !empty($start_date)) {
      $discount = $pdo->prepare('SELECT * FROM discount_detail WHERE kbn_id=? AND id=? AND start_day=? ORDER BY dis_num');
      $discount->bindParam(1, $kbn_id, PDO::PARAM_INT);
      $discount->bindParam(2, $id, PDO::PARAM_INT);
      $discount->bindParam(3, $start_date, PDO::PARAM_STR);

      // ④ 全部入力
    } else {
      $discount = $pdo->prepare('SELECT * FROM discount_detail WHERE kbn_id=? AND id=? AND dis_item=? AND start_day=? ORDER BY dis_num');
      $discount->bindParam(1, $kbn_id, PDO::PARAM_INT);
      $discount->bindParam(2, $id, PDO::PARAM_INT);
      $discount->bindParam(3, $dis_item, PDO::PARAM_INT);
      $discount->bindParam(4, $start_date, PDO::PARAM_STR);
    }

    // SQL 実行
    $discount->execute();
    $items = $discount->fetchAll(PDO::FETCH_ASSOC);
    $message = (count($items) > 0)
      ? count($items) . "件該当しました。"
      : "該当データがありません。";
    echo '<p class="result-message">' . $message . '</p>';
    foreach ($items as $row) {
      //  割引区分の表示変換
      switch ($row['kbn_id']) {
        case '1':
          $k_id = '一会計';
          break;
        case '2':
          $k_id = '商品';
          break;
      }
      //  割引項目の表示変換
      switch ($row['id']) {
        case '1':
          $ida = '日替わり';
          break;
        case '2':
          $ida = '記念日';
          break;
        case '3':
          $ida = '週間';
          break;
      }
      if ($row['kbn_id'] === 1) {
        // 表示用の文字列を作成
        $text =
          ' 区分:' . $k_id .
          ' / 項目:' . $ida .
          ' / 割引名:' . $row['dis_name'] .
          ' / 開始:' . date('Y/m/d', strtotime($row['start_day'])) .
          ' / 終了:' . date('Y/m/d', strtotime($row['end_day']));
      } else {
        $text =
          ' 区分:' . $k_id .
          ' / 項目:' . $ida .
          ' / 割引名:' . $row['dis_name'] .
          ' / 商品:' . $row['dis_item'] .
          ' / 開始:' . date('Y/m/d', strtotime($row['start_day'])) .
          ' / 終了:' . date('Y/m/d', strtotime($row['end_day']));
      }

      $_SESSION['disresult'] = [
        'kbn_id' => $row['kbn_id'],
        'id' => $row['id']
      ];

      echo '<div class="result-item"><a href="discount-edit.php?dis_num=' . $row['dis_num'] . '">'
        . h($text) .
        '</a></div>';
    }
    echo '</div>';
  }
}
?>

<!-- 新しい割引登録 -->
<div class="form-login">
  <?php if (!$done): ?>
    <form action="" method="post">
      <h2 class="title">割引率登録フォーム</h2>
      <table>
        <!-- //割引区分フォーム -->
        <tr>
          <td>1割引区分</td>
          <td>
            <input type="radio" name="kbn_id" value="1">一会計
            <input type="radio" name="kbn_id" value="2">商品
          </td>
        </tr>
        <tr>
          <td>2割引項目</td>
          <td>
            <!--  割引項目入力ボックス(セレクトボックス) -->
            <select name="id">
              <option value=""></option>
              <?php
              $d_is = [
                '日替わり' => 1,
                '記念日' => 2,
                '週間' => 3,
              ];
              foreach ($d_is as $key => $d_i) {
                echo '<option value="', h($d_i), '">', h($key), '</option>';
              }
              ?>
            </select>
          </td>
        </tr>
        <!--  割引の名前 -->
        <tr>
          <td>3割引名</td>
          <td><input type="text" name="dis_name"></td>
        </tr>
        <!--  開始日入力カレンダ -->
        <tr>
          <td>4開始日</td>
          <td>
            <input type="text" id="start_day" name="start_day">
          </td>
          <!-- 終了日入力カレンダ
      <td>終了日</td>
      <td>
        <input type="text" id="end_date" name="end_date">
        <span style="color:green;">※終了日は決まっている場合のみ入力してください</span>
      </td> -->
        </tr>
        <!-- //割引方法 -->
        <tr>
          <td>5割引方法</td>
          <td>
            <input type="radio" name="price_flag" value="0">％（率）
            <input type="radio" name="price_flag" value="1">金額
          </td>
        </tr>
        <!--  割引率入力ボックス -->
        <tr>
          <td>6割引率</td>
          <td>
            <input type="text" name="discount_price">
          </td>
        </tr>
        <tr>
          <td>7商品</td>
          <td>
            <!--  dbより商品のデータを読み込む -->
            <?php
            $product_id = $pdo->query('SELECT name, id FROM product ORDER BY id');
            $products_id = $product_id->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <!-- セレクトボックス（商品表示（最初は空白) -->
            <select name="dis_item">
              <option value=""></option>
              <?php foreach ($products_id as $p_i): ?>
                <option value="<?php echo h($p_i['id']); ?>">
                  <?php echo h($p_i['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>
      <br>
      <!--  登録ボタン -->
      <button type="submit" name="action" value="insert">登録</button>
    </form>
</div>
<br>
<div class="form-login">
  <!-- データ検索用の入力項目 -->
  <?php
    $discount = $pdo->query('SELECT kbn_id, id, discount_price, start_day, end_day FROM discount_detail');
  ?>
  <form action="" method="post">
    <h2 class="title">割引検索フォーム</h2>
    <table>
      <tr>
        <td>割引区分</td>
        <td>
          <input type="radio" name="kbn_id" value="1">一会計
          <input type="radio" name="kbn_id" value="2">商品
        </td>
      </tr>
      <tr>
        <td>割引項目</td>
        <td>
          <!--  割引項目入力ボックス(セレクトボックス) -->
          <select name="id">
            <option value=""></option>
            <?php
            $d_is = [
              '日替わり' => 1,
              '記念日' => 2,
              '週間' => 3,
            ];
            foreach ($d_is as $key => $d_i) {
              echo '<option value="', h($d_i), '">', h($key), '</option>';
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td>商品</td>
        <td>
          <!--  dbより商品のデータを読み込む -->
          <?php
          $product_id = $pdo->query('SELECT name, id FROM product ORDER BY id');
          $products_id = $product_id->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <!-- セレクトボックス（商品表示（最初は空白) -->
          <select name="dis_item">
            <option value=""></option>
            <?php foreach ($products_id as $p_i): ?>
              <option value="<?php echo h($p_i['id']); ?>">
                <?php echo h($p_i['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <td>開始日</td>
        <td>
          <script>
            $(function() {
              $('#start').datepicker({
                dateFormat: 'yy-mm-dd'
              });
            });
          </script>
          <input type="text" id="start" name="start_day">
        </td>
      </tr>
    </table>
    <tr>
      <button type="submit" name='action' value="search">検索</button>
      <button type="submit" name='action' value="reset">リセット</button>
    </tr>
  </form>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>