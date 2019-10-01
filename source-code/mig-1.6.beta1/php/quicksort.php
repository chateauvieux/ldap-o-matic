<script language="php">
function quickSort(&$info, $left, $right, $sortattribute, $order = "asc") 
{
	$min = $left;  
    $max = $right;
	if ($left < $right)
  	{
		// Arbitrarily establishing partition element as the midpoint of
		// the array.
		$pivot = $info[(int)($right + $left)/2][$sortattribute][0]; 
     	while ($max >= $min)
     	{
       		// find the first element that is greater than or equal to 
			// the partition element starting from the left Index.
			while (($right > $min) && (strcasecmp($info[$min][$sortattribute][0],$pivot) < 0))
				$min++; 
			// find an element that is smaller than or equal to
			// the partition element starting from the right Index.
			while (($left < $max) && (strcasecmp($info[$max][$sortattribute][0],$pivot) > 0))
				$max--; 

			// if the indexes have not crossed, swap
			if ($min <= $max)
			{
      			$temp = $info[$min];
      			$info[$min] = $info[$max];
      			$info[$max] = $temp;
				$min++; 
				$max--; 
			}
     	} 
		// If the right index has not reached the left side of array
		// must now sort the left partition
		if ($left < $max)
			quickSort ($info, $left, $max, $sortattribute);
		// If the left index has not reached the right side of array
		// must now sort the right partition.
		if ($min < $right)
			quickSort ($info, $min, $right, $sortattribute);
  	}
  	if ($order == "desc") $info = array_reverse ($info, false);
	return false;
}
</script>		
