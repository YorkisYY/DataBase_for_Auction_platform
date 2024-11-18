<?php
session_start();
include_once("header.php");
require("utilities.php");

#checking user's credential, otherwise swap to welcome page
if (!isset($_SESSION['logged_in']) || $_SESSION['account_type'] != 'buyer'){
	header("Location: browse.php");
	exit();
}

#get the current user's email
$user_email = $_SESSION['username'];
?>

<div class="container">

<h2 class="my-3">My bids</h2>

<?php
  // This page is for showing a user the auctions they've bid on.
  // It will be pretty similar to browse.php, except there is no search bar.
  // This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.

//connect with the database
$conn = ConnectDB();

//perform a query to pull up the auctions they've bid on
$sql = "SELECT Bid.bid_ID,
		Bid.item_ID,
		Item.description,
		(SELECT COUNT(*)FROM Bid AS b WHERE b.item_ID = Item.item_ID) AS num_bids,
		Category.name AS title,
		Item.end_date,
		(SELECT GREATEST(
			(SELECT starting_price FROM Item WHERE Bid.item_ID = Item.item_ID),
			(SELECT COALESCE(MAX(bid_price), 0) FROM Bid WHERE Bid.item_ID = Item.item_ID)
		)) AS bid_price
	FROM Buyer, Bid, Item, Category
	WHERE Buyer.user_ID = Bid.buyer_ID 		AND
		Bid.item_ID = Item.item_ID 		AND
    		Item.category_ID = Category.category_ID AND
		Buyer.email = ?
	GROUP BY item_ID
	ORDER BY bid_time DESC";

//pre-processing searching results, loop through results
If ($stmt = $conn->prepare($sql)){
	$stmt->bind_param("s", $user_email);
	$stmt->execute();

	//get results
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		echo "<ul class = ‘list-group’>";

		//print out all the results as list items
		while ($row = $result -> fetch_assoc()){
			$item_id = $row['item_ID'];
			$title = $row['title'];
                    	$desc = $row['description'];
                    	$price = $row['bid_price'];
                    	$num_bids = $row['num_bids'];
                    	$end_time = new DateTime($row['end_date']);
			print_listing_li($item_id, $title, $desc, $price, $num_bids, $end_time);
		}
		echo "</ul>";
	} else {
		echo "<p> You have not bid on anything...</p>";
	}
	//disconnect with database
	$stmt -> close();
} else {
	echo "<p>Error querying the database.</p>";
}
$conn->close();
?>
</div>
<?php include_once("footer.php")?>
