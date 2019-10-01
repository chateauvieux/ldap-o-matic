<?php   
	/*********************************************/ 
	/*  PHP3 TreeMenu                            */ 
	/*                                           */ 
	/*  (c)1999 Bjorge Dijkstra                  */ 
	/*  email : bjorge@gmx.net                   */ 
	/*  (c)2001 Luis Martins(Kalium)             */
	/*  email : kalium@rocketmail.com            */
	/*                                           */   
	/*********************************************/ 
 
	/*********************************************/ 
	/*  Settings                                 */ 
	/*********************************************/ 
	/*                                           */       
	/*  $treefile variable needs to be set in    */ 
	/*  main file                                */ 
	/*                                           */  
	/*********************************************/ 

	error_reporting (E_ALL ^ E_NOTICE);   
	$script       = $SCRIPT_NAME . "?" . SID; 
	if (isset($selected_class)) $script .= "&class=" . $selected_class->migclassname;
   
	$img_expand   = "menu/drk_icons/tree_expand.gif"; 
	$img_collapse = "menu/drk_icons/tree_collapse.gif"; 
	$img_line     = "menu/drk_icons/tree_vertline.gif";   
	$img_split	= "menu/drk_icons/tree_split.gif"; 
	$img_plus     = "menu/drk_icons/tree_split_plus.gif";
	$img_minus     = "menu/drk_icons/tree_split_minus.gif";
	$img_end      = "menu/drk_icons/tree_end.gif"; 
	$img_leaf     = "menu/drk_icons/tree_leaf.gif"; 
	$img_spc      = "menu/drk_icons/tree_space.gif"; 
   
	/*********************************************/ 
	/*  Read text file with tree structure       */ 
	/*********************************************/ 
   
	/*********************************************/ 
	/* read file to $tree array                  */ 
	/* tree[x][0] -> tree level                  */ 
	/* tree[x][1] -> item text                   */ 
	/* tree[x][2] -> item link                   */ 
	/* tree[x][3] -> link target                 */ 
	/* tree[x][4] -> last item in subtree        */ 
	/*********************************************/ 
 
	$maxlevel=0; 
	$cnt=0; 
   
/*	$fd = fopen($treefile, "r"); 
	if ($fd==0) die("treemenu.inc : Unable to open file ".$treefile); 
	while ($buffer = fgets($fd, 4096))  
	{ 
		$tree[$cnt][0]=strspn($buffer,"."); 
		$tmp=rtrim(substr($buffer,$tree[$cnt][0])); 
		$node=explode("|",$tmp);  
		$tree[$cnt][1]=$node[0]; 
		$tree[$cnt][2]=$node[1]; 
		$tree[$cnt][3]=$node[2]; 
		$tree[$cnt][4]=0; 
		if ($tree[$cnt][0] > $maxlevel) $maxlevel=$tree[$cnt][0];     
		$cnt++; 
	} 
	fclose($fd); 
*/
	$arr = explode ("\n", $treemenu);
	for ($i = 0; $i < count($arr); $i++)
	{
		$buffer = $arr[$i];
		$tree[$cnt][0]=strspn($buffer,"."); 
		$tmp=rtrim(substr($buffer,$tree[$cnt][0])); 
		$node=explode("|",$tmp);  
		$tree[$cnt][1]=$node[0]; 
		$tree[$cnt][2]=$node[1]; 
		$tree[$cnt][3]=$node[2]; 
		$tree[$cnt][4]=0; 
		if ($tree[$cnt][0] > $maxlevel) $maxlevel=$tree[$cnt][0];     
		$cnt++; 
	}
 
	for ($i=0; $i<count($tree); $i++) 
	{ 
		$expand[$i]=0; 
		$visible[$i]=0; 
		$levels[$i]=0; 
	} 
 
	/*********************************************/ 
	/*  Get Node numbers to expand               */ 
	/*********************************************/ 
   
	if ($p!="") $explevels = explode("|",$p); 
   
	$i=0; 
	while($i<count($explevels)) 
	{ 
		$expand[$explevels[$i]]=1; 
		$i++; 
	} 
   
	/*********************************************/ 
	/*  Find last nodes of subtrees              */ 
	/*********************************************/ 
   
	$lastlevel=$maxlevel; 
	for ($i=count($tree)-1; $i>=0; $i--) 
	{ 
		if ( $tree[$i][0] < $lastlevel ) 
		{ 
			for ($j=$tree[$i][0]+1; $j <= $maxlevel; $j++) 
			{ 
				$levels[$j]=0; 
			} 
		} 
		if ( $levels[$tree[$i][0]]==0 ) 
		{ 
			$levels[$tree[$i][0]]=1; 
			$tree[$i][4]=1; 
		} 
		else 
		$tree[$i][4]=0; 
		$lastlevel=$tree[$i][0];   
	} 
   
   
	/*********************************************/ 
	/*  Determine visible nodes                  */ 
	/*********************************************/ 
   
	$visible[0]=1;   // root is always visible 
	for ($i=0; $i<count($explevels); $i++) 
	{ 
		$n=$explevels[$i]; 
		if ( ($visible[$n]==1) && ($expand[$n]==1) ) 
		{ 
			$j=$n+1; 
			while ( $tree[$j][0] > $tree[$n][0] ) 
			{ 
				if ($tree[$j][0]==$tree[$n][0]+1) $visible[$j]=1;      
				$j++; 
			} 
		} 
	} 
   
   
	/*********************************************/ 
	/*  Output nicely formatted tree             */ 
	/*********************************************/ 
   
	for ($i=0; $i<$maxlevel; $i++) $levels[$i]=1; 
 
	$maxlevel++; 
	
   
	echo "<table cellspacing=0 cellpadding=0 border=\"0\" cols=".($maxlevel)." width=".($maxlevel*16+60).">\n"; 
	echo "<tr>"; 
	for ($i=0; $i<$maxlevel; $i++) echo "<td width=10></td>\n"; 
	echo "<td width=60></td></tr>\n";
	$cnt=0;
	while ($cnt<count($tree)) 
	{ 
		if ($visible[$cnt]) 
		{ 
			/****************************************/ 
			/* start new row                        */ 
			/****************************************/       
			echo "<tr>"; 
       
			/****************************************/ 
			/* vertical lines from higher levels    */ 
			/****************************************/ 
			$i=0; 
			while ($i<$tree[$cnt][0]-1)  
			{ 
				if ($levels[$i]==1) echo "<td><img border=\"0\" src=\"".$img_line."\"></td>\n"; 
				$i++; 
			} 
       
			/****************************************/ 
			/* corner at end of subtree or t-split  */ 
			/****************************************/          
			if ($tree[$cnt][4]==1)  
			{ 
				if ($cnt!=0) echo "<td><img border=\"0\" src=\"".$img_end."\"></td>\n"; 
				$levels[$tree[$cnt][0]-1]=0; 
			} 
			else 
			{
				if ($expand[$cnt]==0) 
				{
					if ($tree[$cnt+1][0]>$tree[$cnt][0]) 
					{
						/****************************************/ 
						/* Create expand/collapse parameters    */ 
						/****************************************/ 
						$i=0; $params="&p="; 
						while($i<count($expand)) 
						{ 
							if ( ($expand[$i]==1) && ($cnt!=$i) || ($expand[$i]==0 && $cnt==$i)) 
							{ 
								$params=$params.$i;
								$params=$params."|"; 
							} 
							$i++; 
						} 
						$tmp="&menu=$menu";
						$params=$params.$tmp;
						echo "<td><a href=\"".$script.$params."\"><img border=\"0\" src=\"".$img_plus."\"></a></td>\n"; 
					}
					else
					{
						echo "<td><img border=\"0\" src=\"".$img_split."\"></td>\n";
					}
				}
				else
				{
					if ($tree[$cnt+1][0]>$tree[$cnt][0]) 
					{
						/****************************************/ 
						/* Create expand/collapse parameters    */ 
						/****************************************/ 
						$i=0; $params="&p="; 
						while($i<count($expand)) 
						{ 
							if ( ($expand[$i]==1) && ($cnt!=$i) || ($expand[$i]==0 && $cnt==$i)) 
							{ 
								$params=$params.$i;
								$params=$params."|"; 
							} 
							$i++; 
						} 
						$tmp="&menu=$menu";
						$params=$params.$tmp;
						echo "<td><a href=\"".$script.$params."\"><img border=\"0\" src=\"".$img_minus."\"></a></td>\n"; 
					}
					else
					{
						echo "<td><img border=\"0\" src=\"".$img_split."\"></td>\n";
					}
				}
				$levels[$tree[$cnt][0]-1]=1;     
			}  
       
			/********************************************/ 
			/* Node (with subtree) or Leaf (no subtree) */ 
			/********************************************/ 
			if ($tree[$cnt+1][0]>$tree[$cnt][0]) 
			{ 
         
				/****************************************/ 
				/* Create expand/collapse parameters    */ 
				/****************************************/ 
				$i=0; $params="&p="; 
				while($i<count($expand)) 
				{ 
					if ( ($expand[$i]==1) && ($cnt!=$i) || ($expand[$i]==0 && $cnt==$i)) 
					{ 
						$params=$params.$i;
						$params=$params."|"; 
					} 
					$i++; 
				} 
				$tmp="&menu=$menu";
				$params=$params.$tmp;
                
				if ($expand[$cnt]==0) 
					echo "<td><a href=\"".$script.$params."\"><img border=\"0\" src=\"".$img_expand."\"></a></td>\n"; 
				else 
					echo "<td><a href=\"".$script.$params."\"><img border=\"0\" src=\"".$img_collapse."\"></a></td>\n";          
			} 
			else 
			{ 
				/*************************/ 
				/* Tree Leaf             */ 
				/*************************/ 
				echo "<td><img border=\"0\" src=\"".$img_leaf."\"></td>\n";          
			} 
       
			/****************************************/ 
			/* output item text                     */ 
			/****************************************/ 
			if ($tree[$cnt][2]=="") 
				echo "<td colspan=".($maxlevel-$tree[$cnt][0])."><font size=2>".$tree[$cnt][1]."</font></td>\n"; 
			else 
				echo "<td colspan=".($maxlevel-$tree[$cnt][0])."><a href=\"".$tree[$cnt][2]."\" target=\"".$tree[$cnt][3]."\"><font size=2>".$tree[$cnt][1]."</a></font></td>\n"; 
      
			/****************************************/ 
			/* end row                              */ 
			/****************************************/ 
			echo "</tr>\n";       
		} 
		$cnt++;     
	} 
	echo "</table>\n"; 
	echo "</font>";
 
	/***************************************************/ 
	/* Tree file format                                */ 
	/*                                                 */ 
	/*                                                 */ 
	/* The first line is always of format :            */ 
	/* .[rootname]                                     */ 
	/*                                                 */ 
	/* each line contains one item, the line starts    */  
	/* with a series of dots(.). Each dot is one level */ 
	/* deeper. Only one level at a time once is allowed*/ 
	/* Next comes the come the item name, link and     */ 
	/* link target, seperated by a |.                  */ 
	/*                                                 */   
	/* example:                                        */ 
	/*                                                 */   
	/* .top                                            */ 
	/* ..category 1                                    */ 
	/* ...item 1.1|item11.htm|main                     */ 
	/* ...item 2.2|item12.htm|main                     */ 
	/* ..category 2|cat2overview.htm|main              */ 
	/* ...item 2.1|item21.htm|main                     */ 
	/* ...item 2.2|item22.htm|main                     */ 
	/* ...item 2.3|item23.htm|main                     */ 
	/*                                                 */   
	/***************************************************/ 
?> 
