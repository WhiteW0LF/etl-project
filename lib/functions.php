<?php
require_once 'config.php';


function get_curl_page($url) {
	$ch = curl_init();	
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HEADER, 1); // читать заголовок
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);  
	curl_close($ch);
	$dom = str_get_html($result);
	//return $result;
	return $dom;
}

function get_product_data($dom) {
	//$dom = str_get_html($result);
	$titles = $dom->find('h1.selenium-KP-product-name');
	// echo $titles[0]->plaintext;
	$product['model'] = $titles[0]->plaintext;
	
	//echo '<br/>';
	$description = $dom->find('.product-attributes');
	//echo 'Description: '.$description[0]->plaintext;
	$product['description'] = $description[0]->plaintext;
	//echo '<br/>';
	$category = $dom->find('a.selenium-last-but-one-breadcrumb');
	// echo 'Category: '.$category[0]->plaintext;
	$product['category'] = $category[0]->plaintext;
	//echo '<br/>';
	$manufacturers = $dom->find('.description-tech-details tr');
	// echo 'Manufacturer: '.$manufacturer[0]->plaintext;
	$tr_count = count($manufacturers);

	if ($tr_count == 50) {
		$tr_count_rs = $tr_count - 3;
	}
	else $tr_count_rs = $tr_count - 4;

	$producent = $manufacturers[$tr_count_rs]->plaintext;
	$product["manufacturer"] = $producent;
	// echo 'Manufacturer: '.$producent;
	//echo '<br/>';

	$title = $titles[0]->plaintext;
	
	return $product;
	//get_product_reviews($dom);
}

function get_product_reviews($dom,$product_id) {
	//$dom = str_get_html($result);
	//echo 'reviews start here<br/>';
	//echo '<br/>';
	$i=0;
	$count_review_positive = 0;
	$count_review_negative = 0;
	
	$count_rev = 0;
	$count_page = 0;
	$reviews = $dom->find('div.opinion-item');
	if ($dom->find('h1.product-name',0)) {
		$count_page++;
	}
	//get_single_review($reviews);
	foreach($reviews as $review) {
		$review_id = $review->find('div[id]',0);
		$review_id = $review_id->{'id'};
		$bdreview[$i]['id'] = $review_id;
		
		file_put_contents('files/'.$bdreview[$i]['id'].'.html',$review);
		
		$review_text = $review->find('.opinion-text',0)->plaintext;
		$bdreview[$i]['text'] = $review_text;
		// echo 'review: '.$review_text.'<br/>';
		
		$rewiew_stars = $review->find('div.stars-rating',0)->getAttribute("title");
		

		// echo 'stars: '.$rewiew_stars.'<br/>';
		$bdreview[$i]['stars'] = 0;

		if ($rewiew_stars == 'rewelacyjny') {
			$bdreview[$i]['stars']= 5;
		} if ($rewiew_stars == 'dobry') {
			$bdreview[$i]['stars']= 4;
		} if ($rewiew_stars == 'w porządku') {
			$bdreview[$i]['stars']= 3;
		} if ($rewiew_stars == 'wystarczający') {
			$bdreview[$i]['stars']= 2;
		} if ($rewiew_stars == 'nieudany') {
			$bdreview[$i]['stars']= 1;
		} 


		$review_time = $review->find('.opinion-date',0)->plaintext;
		// echo 'Time: '.$review_time.'<br/>';
		$bdreview[$i]['time']=$review_time;

		$review_author = $review->find('.opinion-nick',0)->plaintext;
		// echo 'Review author: '.$review_author.'</br>';
		$bdreview[$i]['author'] = $review_author;

		$review_rec = $review->find('.opinion-title',0)->plaintext;
		// echo 'Recomendation : '.$review_rec.'<br/>';
		$bdreview[$i]['recommendation'] = $review_rec;

		
		$review_positive = $rewiew_stars;
		if ($review_positive == 'rewelacyjny' || $review_positive == 'dobry'|| $review_positive == 'w porządku') {
			$count_review_positive++;
		} else {
			$count_review_negative++;
		}
		
		
		//$text_review[$i] = $review_text;
		//echo '<br/>';
		$i++;
		$count_rev++;
		
	}
	
	$main_url = 'www.euro.com.pl';
	$pagenavi = $dom->find('.paging-active',0);
	if ($pagenavi) {
		$pagenavi = $pagenavi->next_sibling();
	}
	if ($pagenavi) {
		while ($pagenavi) {
			$link_page = $pagenavi->find('a',0)->href;
			$url = $main_url.$link_page;
			// echo $url;
			$dom = get_curl_page($url);
			
			$reviews = $dom->find('div.opinion-item');
			$pagenavi = $dom->find('.paging-active',0);
			
			foreach($reviews as $review) {
				$review_id = $review->find('a[data-review-id]',0);
				$review_id = $review_id->{'data-review-id'};
				$bdreview[$i]['id'] = $review_id;
				file_put_contents('files/'.$bdreview[$i]['id'].'.html',$review);
			
				
				$review_text = $review->find('.opinion-text',0)->plaintext;
				$bdreview[$i]['text'] = $review_text;
				// echo 'review: '.$review_text.'<br/>';
				
				$rewiew_stars = $review->find('div.stars-rating',0)->getAttribute("title");
				

				// echo 'stars: '.$rewiew_stars.'<br/>';
				$bdreview[$i]['stars'] = $review_stars;

				$review_time = $review->find('.opinion-date',0)->plaintext;
				// echo 'Time: '.$review_time.'<br/>';
				$bdreview[$i]['time']=$review_time;

				$review_author = $review->find('.opinion-nick',0)->plaintext;
				// echo 'Review author: '.$review_author.'</br>';
				$bdreview[$i]['author'] = $review_author;

				$review_rec = $review->find('.opinion-title',0)->plaintext;
				// echo 'Recomendation : '.$review_rec.'<br/>';
				$bdreview[$i]['recommendation'] = $review_rec;

				$review_positive = $rewiew_stars;
				if ($rewiew_stars == 'rewelacyjny' || $rewiew_stars == 'dobry'|| $rewiew_stars == 'w porządku') {
					$count_review_positive++;
				} else {
					$count_review_negative++;
				}
				
				//$text_review[$i] = $review_text;
				//echo '<br/>';
				$i++;
				$count_rev++;
				
			}
			$count_page++;
			
		}
	}
	
	echo 'Count recommended: '.$count_review_positive.'</br>';
	echo 'Count not recommended: '.$count_review_negative.'</br>';
	echo 'Count review:  '.$count_rev.'</br>';
	echo 'Count pages: '.$count_page;
	$bdreview[0]['statistic']['count_positive_review'] = $count_review_positive;
	$bdreview[0]['statistic']['count_positive_negative'] = $count_review_negative;
	$bdreview[0]['statistic']['count_review'] = $count_rev;
	$bdreview[0]['statistic']['count_page'] = $count_page;
	$bdreview[0]['product_id'] = $product_id;
	return $bdreview;
}

error_reporting(E_ALL & ~E_NOTICE);