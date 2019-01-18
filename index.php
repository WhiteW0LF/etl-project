<?php
header('Content-Type: text/html; charset=utf-8');

ini_set('memory_limit', '-1');
session_start();
require_once('lib/simple_html_dom.php');
require_once('lib/functions.php');
require_once('lib/Db.php');



$main_url = 'https://www.euro.com.pl/';
if (isset($_POST['extract'])) {
	$productID = $_POST['productId'];
	$url = $main_url.$productID;
    $result = get_curl_page($url);

	if ($result) {

        $_SESSION['product'] = get_product_data($result);

		$_SESSION['bdreview'] = get_product_reviews($result,$_POST['productId']);

	}
	
}
if (isset($_POST['transform'])) {
	$_SESSION['product']['id'] = $_POST['productId'];
	$_SESSION['product']['manufacturer'] = ltrim($_SESSION['product']['manufacturer']);
	$_SESSION['product']['description'] = ltrim($_SESSION['product']['description']);
	for ($j=0;$j<count($_SESSION['bdreview']);$j++) {
		$_SESSION['bdreview'][$j]['author'] = ltrim($_SESSION['bdreview'][$j]['author']);
		$_SESSION['bdreview'][$j]['recommendation'] = ltrim($_SESSION['bdreview'][$j]['recommendation']);
		$_SESSION['bdreview'][$j]['text'] = htmlspecialchars($_SESSION['bdreview'][$j]['text']);
		$reit = explode('/',$_SESSION['bdreview'][$j]['stars']);
		$_SESSION['bdreview'][$j]['stars'] = $reit[0];
	}
	echo 'Transform end !';
	$file_name = $_SESSION['product']['id'];
	$replace = array(" ", "-", "/");
	$final_name = str_replace($replace, "", $file_name);
	$files = 'files/'.$final_name;
	// echo $_SESSION['product']['id'];
	file_put_contents($files,$_SESSION['product']);
}
if (isset($_POST['load'])) {
	
	$db = new Db();
	$db = $db->getConnection();
	$prods = $db->query('SELECT id FROM products WHERE id="'.$_SESSION['product']['id'].'"');

	$row = $prods->fetchAll();
	
	// var_dump($row[0]['id']);
	//var_dump($_SESSION['product']['id']);
	//$sql=("UPDATE products SET category = :category, manufacturer = :manufacturer, model = :model, description = :description WHERE id = :id");
	if ($row[0]['id'] == $_SESSION['product']['id']) {
		//$sql=("UPDATE products SET category = :category WHERE id = :id");	
		$sql=("UPDATE products SET category = :category, model = :model, manufacturer = :manufacturer, description = :description WHERE id = :id");
	} else {
		//$sql=("INSERT INTO products (id, category, manufacturer, model, description) VALUES (:id, :category, :manufacturer, :model, :description)");
		$sql=("INSERT INTO products (id, category, manufacturer, model, description) VALUES (:id, :category, :manufacturer, :model, :description)");
	}
	//var_dump($_SESSION['product']['manufacturer']);
	$insprod = $db->prepare($sql);                                          
	$insprod->bindParam(':id', $_SESSION['product']['id'], PDO::PARAM_INT);       
	$insprod->bindParam(':category', $_SESSION['product']['category'], PDO::PARAM_STR); 
	$insprod->bindParam(':manufacturer', $_SESSION['product']['manufacturer'], PDO::PARAM_STR);
	// use PARAM_STR although a number  
	$insprod->bindParam(':model', $_SESSION['product']['model'], PDO::PARAM_STR); 
	$insprod->bindParam(':description', $_SESSION['product']['description'], PDO::PARAM_STR);   
	$insprod->execute();
	//var_dump($_SESSION['bdreview'][1]);
	/*while ($row = $prods->fetch())
	{
		echo $row['id'] . "\n";
	}*/
	//echo '<br/>'.$_SESSION['bdreview'][0]['product_id'];
	$ids_rev = array();
	$reviews = $db->query('SELECT id FROM reviews WHERE id_product="'.$_SESSION['bdreview'][0]['product_id'].'"');
	while ($row = $reviews->fetch())
	{
		//echo $row['id'] . "\n";
		$ids_rev[] = $row['id'];
	}
	//var_dump($ids_rev);
	$upd = 0;
	$insert =0;
	
	for ($k=0;$k<count($_SESSION['bdreview']);$k++) {
	if (in_array($_SESSION['bdreview'][$k]['id'], $ids_rev)) {
		$sql_rev=("UPDATE reviews SET id = :id, id_product = :id_product, text_review = :text_review, stars = :stars, time = :time, author = :author, recommendation = :recommendation WHERE id = :id");
		$upd++;
	} else {
		$sql_rev=("INSERT INTO reviews (id, id_product, text_review, stars, time, author, recommendation) VALUES (:id, :id_product, :text_review, :stars, :time, :author, :recommendation)");
		$insert++;
	}
		$insrev = $db->prepare($sql_rev);   
		$insrev->bindParam(':id', $_SESSION['bdreview'][$k]['id'], PDO::PARAM_INT);       
		$insrev->bindParam(':id_product', $_SESSION['bdreview'][0]['product_id'], PDO::PARAM_INT); 
		$insrev->bindParam(':text_review', $_SESSION['bdreview'][$k]['text'], PDO::PARAM_STR); 
		$insrev->bindParam(':stars', $_SESSION['bdreview'][$k]['stars'], PDO::PARAM_STR);  
		$insrev->bindParam(':time', $_SESSION['bdreview'][$k]['time'], PDO::PARAM_STR);   	
		$insrev->bindParam(':author', $_SESSION['bdreview'][$k]['author'], PDO::PARAM_STR); 
		$insrev->bindParam(':recommendation', $_SESSION['bdreview'][$k]['recommendation'], PDO::PARAM_STR); 
		$insrev->execute();
	}
	echo '<br>Count reviews update: '.$upd.'</br>Count reviews insert(new): '.$insert;
	echo '<br/>'.'Load end !';
	foreach ($_SESSION['bdreview'] as $writes) {
		file_put_contents('files/'.$writes['id'].'.txt',$writes);
	}

	unset($_SESSION['bdreview']);
	unset($_SESSION['product']);
		
}
if (isset($_POST['etl'])) {
	/*extract */
	
	$productID = $_POST['productId'];
	$url = $main_url.$productID;
    $result = get_curl_page($url);

	if ($result) {

        $_SESSION['product'] = get_product_data($result);

		$_SESSION['bdreview'] = get_product_reviews($result,$_POST['productId']);

	}
	echo '<br/>'.'Extract end !';

	/*transform*/
	$_SESSION['product']['id'] = $_POST['productId'];
	$_SESSION['product']['manufacturer'] = ltrim($_SESSION['product']['manufacturer']);
	$_SESSION['product']['description'] = ltrim($_SESSION['product']['description']);
	for ($j=0;$j<count($_SESSION['bdreview']);$j++) {
		$_SESSION['bdreview'][$j]['author'] = ltrim($_SESSION['bdreview'][$j]['author']);
		$_SESSION['bdreview'][$j]['recommendation'] = ltrim($_SESSION['bdreview'][$j]['recommendation']);
		$_SESSION['bdreview'][$j]['text'] = htmlspecialchars($_SESSION['bdreview'][$j]['text']);
		$reit = explode('/',$_SESSION['bdreview'][$j]['stars']);
		$_SESSION['bdreview'][$j]['stars'] = $reit[0];
	}
	echo '<br/>'.'Transform end !';
	$file_name = $_SESSION['product']['id'];
	$replace = array(" ", "-", "/");
	$final_name = str_replace($replace, "", $file_name);
	$files = 'files/'.$final_name;
	// echo $_SESSION['product']['id'];
	file_put_contents($files,$_SESSION['product']);

	/*load*/
	$db = new Db();
	$db = $db->getConnection();
	$prods = $db->query('SELECT id FROM products WHERE id="'.$_SESSION['product']['id'].'"');
	$row = $prods->fetchAll();

	// var_dump($row[0]['id']);
	//var_dump($_SESSION['product']['id']);
	//$sql=("UPDATE products SET category = :category, manufacturer = :manufacturer, model = :model, description = :description WHERE id = :id");
	if ($row[0]['id'] == $_SESSION['product']['id']) {
		$sql=("UPDATE products SET category = :category, model = :model, manufacturer = :manufacturer, description = :description WHERE id = :id");
	} else {
		$sql=("INSERT INTO products (id, category, manufacturer, model, description) VALUES (:id, :category, :manufacturer, :model, :description)");
	}
	//var_dump($_SESSION['product']['manufacturer']);
	$insprod = $db->prepare($sql);                                          
	$insprod->bindParam(':id', $_SESSION['product']['id'], PDO::PARAM_INT);      
	// use PARAM_STR although a number   
	$insprod->bindParam(':category', $_SESSION['product']['category'], PDO::PARAM_STR); 
	$insprod->bindParam(':manufacturer', $_SESSION['product']['manufacturer'], PDO::PARAM_STR);
	$insprod->bindParam(':model', $_SESSION['product']['model'], PDO::PARAM_STR); 
	$insprod->bindParam(':description', $_SESSION['product']['description'], PDO::PARAM_STR);   
	$insprod->execute();
	//var_dump($_SESSION['bdreview'][1]);

	//echo '<br/>'.$_SESSION['bdreview'][0]['product_id'];
	$ids_rev = array();
	$reviews = $db->query('SELECT id FROM reviews WHERE id_product="'.$_SESSION['bdreview'][0]['product_id'].'"');
	while ($row = $reviews->fetch())
	{
		//echo $row['id'] . "\n";
		$ids_rev[] = $row['id'];
	}
	//var_dump($ids_rev);
	$upd = 0;
	$insert =0;
	
	for ($k=0;$k<count($_SESSION['bdreview']);$k++) {
	if (in_array($_SESSION['bdreview'][$k]['id'], $ids_rev)) {
		$sql_rev=("UPDATE reviews SET id = :id, id_product = :id_product, text_review = :text_review, stars = :stars, time = :time, author = :author, recommendation = :recommendation WHERE id = :id");
		$upd++;
	} else {
		$sql_rev=("INSERT INTO reviews (id, id_product, text_review, stars, time, author, recommendation) VALUES (:id, :id_product, :text_review, :stars, :time, :author, :recommendation)");
		$insert++;
	}
		$insrev = $db->prepare($sql_rev);   
		$insrev->bindParam(':id', $_SESSION['bdreview'][$k]['id'], PDO::PARAM_INT);       
		$insrev->bindParam(':id_product', $_SESSION['bdreview'][0]['product_id'], PDO::PARAM_INT); 
		// $insrev->bindParam(':review_plus', $_SESSION['bdreview'][$k]['plus'], PDO::PARAM_STR);
		// // use PARAM_STR although a number  
		// $insrev->bindParam(':review_minus', $_SESSION['bdreview'][$k]['minus'], PDO::PARAM_STR); 
		$insrev->bindParam(':text_review', $_SESSION['bdreview'][$k]['text'], PDO::PARAM_STR); 
		$insrev->bindParam(':stars', $_SESSION['bdreview'][$k]['stars'], PDO::PARAM_STR);  
		$insrev->bindParam(':time', $_SESSION['bdreview'][$k]['time'], PDO::PARAM_STR);   	
		$insrev->bindParam(':author', $_SESSION['bdreview'][$k]['author'], PDO::PARAM_STR); 
		$insrev->bindParam(':recommendation', $_SESSION['bdreview'][$k]['recommendation'], PDO::PARAM_STR); 
		$insrev->execute();
		//file_put_contents('files/'.$_SESSION['bdreview'][$k]['id'].'.txt',$_SESSION['bdreview']);
	}
	echo '<br>Count reviews update: '.$upd.'</br>Count reviews insert(new): '.$insert;
	echo '<br/>'.'Load end !';
	foreach ($_SESSION['bdreview'] as $writes) {
		file_put_contents('files/'.$writes['id'].'.txt',$writes);
	}

	unset($_SESSION['bdreview']);
	unset($_SESSION['product']);
		
}
if (isset($_POST['csv'])) {
	header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="export.csv";');

    $db = new Db();
	$db = $db->getConnection(); // bd connection

    $sql = 'SELECT * FROM reviews WHERE id_product="'.$_POST['productId'].'"';  // SQL-запрос
    $result = $db->query($sql);  // Выполняем запрос

    $fp = fopen('php://output', 'w');  // Открываем поток для записи

    while($row = $result->fetch(PDO::FETCH_ASSOC)) {  // Перебираем строки
        fputcsv($fp, $row, ";");  // Записываем строки в поток
		//$res[]=$row;
		
    }
	//var_dump($res);
	//id,id_product,review_plus,review_minus,text_review,stars,author,time,recommendation
}
if (isset($_POST['csvall'])) {
	header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="allreviews.csv";');

    $db = new Db();
	$db = $db->getConnection(); // bd connection

    $sql = 'SELECT * FROM reviews';  // SQL-запрос
    $result = $db->query($sql);  // Выполняем запрос

    $fp = fopen('php://output', 'w');  // Открываем поток для записи

    while($row = $result->fetch(PDO::FETCH_ASSOC)) {  // Перебираем строки
        fputcsv($fp, $row, ";");  // Записываем строки в поток
		//$res[]=$row;
		
    }
	//var_dump($res);
	//id,id_product,review_plus,review_minus,text_review,stars,author,time,recommendation
}


?>


<html>
<head>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	<link rel="stylesheet" type="text/css" href="style.css">

    <title>Etl project</title>
  </head>
  <body style="
    padding: 20px;
">
    <div class='main'>
	<div class="menu">
	<a href="index.php" class="btn btn-outline-dark">Parser</a>
	</div>
		<div class="content">
			<div class="parser-form">
				<form method="POST" class="etl_form">
					<div class="form-block">
						<input name="productId" type="text"  value="<?php echo $_POST['productId']?>" required/>
						<input name="extract" type="submit" class="btn btn-outline-dark" value="Extract"/>
						<form method="post">
						<button name="transform" class="btn btn-outline-dark" type="submit"<?php if(!$_POST['extract']){ echo 'disabled'; }?> value="1">Transform</button>
						</form>
						<button name="load" class="btn btn-outline-dark" type="submit" <?php if(!isset($_POST['transform'])){ echo 'disabled'; }?>>Load</button>
						<br/>
						<button name="etl" type="submit" class="etl_button btn btn-outline-dark">ETL in one click</button>
						<br/>
						<button name="csv" type="submit" class="etl_button btn btn-outline-dark">Export in csv</button>
					</div>
				</form>
				<form method="POST" class="form_csv">
					<button name="csvall" type="submit" class="etl_button btn btn-outline-dark">Export all reviews in csv from database</button>
				</form>
			</div>
		</div>
		
	</div>
	<?php
	$db = new Db();
	$db = $db->getConnection();
	
	
	$products = $db->query('SELECT id,model,category,manufacturer,description FROM products WHERE id="'.$_POST['productId'].'"');
	if($_POST['productId'] == false) {
		exit();
	}
	while ($row=$products->fetch()) {
		$prod['id']=$row['id'];
		$prod['model']=$row['model'];
		$prod['category']=$row['category'];
		$prod['description']=$row['description'];
	}
	
	$reviews = $db->query('SELECT id, text_review, stars, author, recommendation, time FROM reviews WHERE id_product="'.$_POST['productId'].'"');
	while ($rev = $reviews->fetch(PDO::FETCH_ASSOC)) {
		
		$res[]=$rev;
		
	}
	
	?>
	</div>
		<div class="content">
		<div class="categoery"><span>Category: </span><?php echo $prod['category'];?></div>
			<div class="product">
				<div class="title"><span>Model: </span><?php echo $prod['model'];?></div>
				<div class="description"><span>Description: </span><?php echo substr($prod['description'],0,10000); if (strlen($prod['description'])>10000) echo '...';?></div>
				<div class="review">
				<center>Reviews</center>
				<?php //var_dump(($res));?>
				<?php foreach($res as $rv) :?>
					<div id="<?php echo $rv['id'] ?>" class="review-block">	
						<div><span>Author:</span> <?php echo $rv['author'];?></div>
						<div class="comment-block"><span>Comment: </span><?php echo $rv['text_review']?></div>
						<div class="stars"><span>Raiting: </span><?php echo $rv['stars']?> stars</div>
						<div class="recommendation"><span>Recommendation: </span><?php echo $rv['recommendation'];?></div>
						<div class="time_rev"><span>Time review: </span><?php echo $rv['time'];?></div>
					</div>
				<?php endforeach;?>
				</div>
			</div>
		</div>
	</div>
	

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
  </body>
</html>