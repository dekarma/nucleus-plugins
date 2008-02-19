  <?php
    $no_friend_c = "checked=\"checked\"";
    $prod_c = "checked=\"checked\"";
    $geo_c = "checked=\"checked\"";
    $fam_c = "checked=\"checked\"";
    $xfnid = explode(" ", $xfn);

    foreach ($xfnid as $i) {
      switch($i) {
        case "me":
	  $me_c = "checked=\"checked\"";
	  break;
	case "contact":
	  $contact_c = "checked=\"checked\"";
	  $no_friend_c = "";
	  break;
	case "acquaintance":
	  $aqu_c = "checked=\"checked\"";
	  $no_friend_c = "";
	  break;
	case "friend":
	  $friend_c = "checked=\checked\"";
	  $no_friend_c = "";
	  break;
	case "met":
	  $met_c = "checked=\"checked\"";
	  break;
	case "co-worker":
	  $co_work_c = "checked=\"checked\"";
	  $prof_c = "";
	  break;
	case "colleague":
	  $col_c = "checked=\"checked\"";
	  $prof_c = "";
	  break;
	case "co-resident":
	  $co_res_c = "checked=\"checked\"";
	  $geo_c = "";
	  break;
	case "neighbor":
	  $nei_c = "checked=\"checked\"";
	  $geo_c = "";
	  break;
	case "child":
	  $chi_c = "checked=\"checked\"";
	  $fam_c = "";
	  break;
	case "parent":
	  $par_c = "checked=\"checked\"";
	  $fam_c = "";
	  break;
	case "sibling":
	  $sib_c = "checked=\"checked\"";
	  $fam_c = "";
	  break;
	case "spouse":
	  $spo_c = "checked=\"checked\"";
	  $fam_c = "";
	  break;
	case "kin":
	  $kin_c = "checked=\"checked\"";
	  $fam_c = "";
	  break;
	case "muse":
	  $mus_c = "checked=\"checked\"";
	  break;
	case "crush":
	  $cru_c = "checked=\"checked\"";
	  break;
	case "date":
	  $dat_c = "checked=\"checked\"";
	  break;
	case "sweetheart":
	  $swe_c = "checked=\"checked\"";
	  break;
      }
    }
  ?>
  <input name="identity" value="me" id="me" type="checkbox" <?php echo $me_c; ?> />&nbsp;me<br/>
  <b>friendship</b> 
  <input class="valinp" name="friendship" value="contact" id="friendship-contact" type="radio" <?php echo $contact_c; ?> />&nbsp;contact
  <input class="valinp" name="friendship" value="acquaintance" id="friendship-aquaintance" type="radio" <?php echo $aqu_c; ?> />&nbsp;acquaintance
  <input class="valinp" name="friendship" value="friend" id="friendship-friend" type="radio" <?php echo $friend_c; ?> />&nbsp;friend 
  <input class="valinp" name="friendship" value="" id="friendship-none" type="radio" <?php echo $no_friend_c; ?> />&nbsp;none
  <br/>
  <b>physical</b> <input class="valinp" name="physical" value="met" id="met" type="checkbox" <?php echo $met_c; ?> />&nbsp;met
  <br/>
  <b>professional</b>
  <input class="valinp" name="professional" value="co-worker" id="co-worker" type="checkbox" <?php echo $co_work_c; ?> />&nbsp;co-worker 
  <input class="valinp" name="professional" value="colleague" id="colleague" type="checkbox" <?php echo $col_c; ?> />&nbsp;colleague
  <br/>
  <b>geographical</b>
  <input class="valinp" name="geographical" value="co-resident" id="co-resident" type="radio" <?php echo $co_res_c; ?> />&nbsp;co-resident 
  <input class="valinp" name="geographical" value="neighbor" id="neighbor" type="radio" <?php echo $nei_c; ?> />&nbsp;neighbor 
  <input class="valinp" name="geographical" value="" id="geographical-none" type="radio" <?php echo $geo_c; ?> />&nbsp;none
  <br/>
  <b>family</b> 
  <input class="valinp" name="family" value="child" id="family-child" type="radio" <?php echo $chi_c; ?> />&nbsp;child 
  <input class="valinp" name="family" value="parent" id="family-parent" type="radio" <?php echo $par_c; ?> />&nbsp;parent 
  <input class="valinp" name="family" value="sibling" id="family-sibling" type="radio" <?php echo $sib_c; ?> />&nbsp;sibling 
  <input class="valinp" name="family" value="spouse" id="family-spouse" type="radio" <?php echo $spo_c; ?> />&nbsp;spouse 
  <input class="valinp" name="family" value="kin" id="family-kin" type="radio" <?php echo $kin_c; ?> />&nbsp;kin 
  <input class="valinp" name="family" value="" id="family-none" type="radio" <?php echo $fam_c; ?> />&nbsp;none
  <br/>
  <b>romantic</b>
  <input class="valinp" name="romantic" value="muse" id="muse" type="checkbox"<?php echo $mus_c; ?> />&nbsp;muse
  <input class="valinp" name="romantic" value="crush" id="crush" type="checkbox" <?php echo $cru_c; ?> />&nbsp;crush 
  <input class="valinp" name="romantic" value="date" id="date" type="checkbox" <?php echo $dat_c; ?> />&nbsp;date 
  <input class="valinp" name="romantic" value="sweetheart" id="sweetheart" type="checkbox" <?php echo $swe_c; ?> />&nbsp;sweetheart
  <br/>
  <input type="reset" />
