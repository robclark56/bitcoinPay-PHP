<?php
/*
 LightningPay  - Example eCommerce checkout page
 
 Example URLs
  https://my.web.server/path/StoreCheckout.php
  https://my.web.server/path/StoreCheckout.php?order_id=100
  https://my.web.server/path/StoreCheckout.php?testnet=1
  https://my.web.server/path/StoreCheckout.php?testnet=1&order_id=100
*/

define('CURRENCY','USD');
$testnet = $_GET['testnet'] > 0;
$chain_name = $testnet?'Testnet':'Mainnet';
$order_id=$_GET['order_id']?$_GET['order_id']:42;	//Default order = 42. Cheaper alternative = 100

$orders[100]['products'][] = 
		array(	
			'qty' => 1,
			'price_ea' => 0.10,
			'desc' =>'HODL Sticker'
		);
		
$orders[42]['products'][] = 
		array(
			'qty' => 1,
			'price_ea' => 10.00,
			'desc' =>'Pan Galactic Gargle Blaster'
		);
$orders[42]['products'][] = 
		array(
			'qty' => 2,
			'price_ea' => 5.00,
			'desc' =>'Book of Vogon Poetry'
		);
$orders[42]['products'][] = 
		array(
			'qty' => 3,
			'price_ea' => 20.00,
			'desc' =>'HODL Teeshirts'
		);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Store Checkout</title>
<style> 
body { background-color: <?php echo $testnet?'CornflowerBlue ':'SkyBlue';?>;} 
table{ background-color: white;} 
</style>
</head>

<body>
<table>
<td><img src="https://github.com/lightningnetwork/lnd/raw/master/logo.png"  width="150" height="150">
<td><img src="https://image.freepik.com/free-icon/store_318-49896.jpg" width="150" height="150">
</table>
<h1>All Electric <?php echo $chain_name?> Emporium</h1>

<h2>Your Shopping Cart - Order <?php echo $order_id?></h2>
<?php //echo "<p><b>Current Exchange Rate:</b> 1 BTC = $ExchRate .</p>";?>
<table border='1'>
 <tr><th>Qty<th>Description<th>Price each<th>Price Total<br><?php echo CURRENCY;?></tr>
 <?php
 $order = $orders[$order_id];
 $grand_total = 0;
 foreach($order['products'] as $product){
  $qty   = $product['qty'];
  $desc  = $product['desc'];
  $price = number_format($product['price_ea'],2);
  $total = number_format($product['qty'] * $product['price_ea'],2);
  $grand_total += $total;
  echo "<tr><td>$qty<td>$desc<td align='right'>$ $price<td align='right'>$ $total</tr>";
 }
 echo '<tr><th colspan="3" align="right">Total<th align="right">$ '.number_format($grand_total,2).'</tr>';
 echo '<tr><th colspan="4" align="right">'; 
 ?>
 <form action="lightningPay.php<?php echo $testnet?'?testnet=1':'';?>" method="post"> 
  <input type="hidden" name="memo" value="Order <?php echo $order_id;?>">  
  <input type="hidden" name="amount" value="<?php echo $grand_total;?>">
  <input type="hidden" name="currency" value="<?php echo CURRENCY;?>"> 
  <input type="hidden" name="amount_format" value="<?php echo '$'.number_format($grand_total,2);?>">
  <button type="submit">Pay Now</button><br>
 </form>
 <?php  echo '</tr>';?>
</table>

</body>

</html>
