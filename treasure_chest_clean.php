<?php
// Analysis of Stardew Valley treasure chest item chances.
// Based on Tools/FishingRod::openTreasureMenuEndFunction

// On/off flags for whether each of wild_bait, lost books, and artifacts are possible;
// whether doublefish caught; whether it's spring (rice possible)
foreach (array(1,0) as $spring) {
  foreach (array(1,0) as $doublefish) {
    foreach (array(1,0) as $wild_bait) {
      foreach (array(1,0) as $book) {
        foreach (array(1,0) as $artifact) {
          // [0,1,2] same for most checks -- EXCEPT diamond in gems section and luckmod
          foreach (array(5,3,1,0) as $score) {
            // [0,1] skill same; [2,3,4] same; [6+] same
            foreach (array(10,5,4,0) as $skill) {
              // 0 as default daily luck; maximum and minimum possible values
              foreach (array(0,-0.1,0.125) as $daily_luck) {
                foreach (array(0,1,2,3) as $luck_level) {
                  // Get chances from a single pass through the main loop with these conditions
                  $chances = do_one_loop();
                   // Integrate each value over multiple loops
                  foreach ($chances as $item_id => $chance) {
                    $chances[$item_id] = integrate_loops($chance);
                  }
                  // Extra work to handle rare first_loop_only cases
                  foreach ($fixed_chances as $item_id => $chance) {
                    @ $chances[$item_id] += $chance;
                  }
                  foreach ($chances as $item_id => $chance) {
                    if (!isset($min_chances[$item_id]) || $chance<$min_chances[$item_id]) {
                      $min_chances[$item_id] = $chance;
                    }
                    if (!isset($max_chances[$item_id]) || $chance>$max_chances[$item_id]) {
                      $max_chances[$item_id] = $chance;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
 /*
 * Determine chances for each item to be added on one pass through the item-adding
 * loop in FishingRod::openTreasureMenuEndFunction.
 *
 * This function mimics the functionality of openTreasureMEnuEndFucntion, except instead
 * of selecting one item, it tracks the chances for every possible item.
 * Which means instead of doing a whole series of if / elseif / elseif clauses to
 * work through the items, this code mostly avoids elseif -- instead, impact of all
 * the previous possible items are tracked through $noitem_chance.
 */
function do_one_loop($options=array()) {
  // The conditions being used for this calculation
  global $score, $skill, $daily_luck, $luck_level, $wild_bait, $book, $artifact, $doublefish, $spring;
  // The calculation result: an array of chances for each item, keyed by item ID.
  global $chances;
  // Extra results: special cases where chances should NOT be integrated over multiple loops.
  global $fixed_chances;
  // Variables used to track how previously-calculated items impact subsequent items.
  global $base_chance, $noitem_chance, $oneitem_chance;
   $fixed_chances = $chances = array();
   reset_base(1);
  // Pre-switch items: rice shoots and wild bait
  // These items are added in addition to primary item and have next-to-no effect on chance
  // of primary item -- therefore reset_base is called after these items are done.
  // Only repercussions of these pre-switch items is when code does tests of treasures.count --
  // meaning only relevant at end of special-items processing.
  if ($spring)
    add_chance(273,0.1,array('ignore_noitem'=>TRUE));
  if ($wild_bait && $doublefish)
    add_chance(774,0.5,array('ignore_noitem'=>TRUE));
   // Store noitem_chance after pre-switch items done: for sake of treasures.count - specific tests
  // done at end of special-items
  $prelim_noitem_chance = $noitem_chance;
  $prelim_oneitem_chance = $oneitem_chance;
   // 4 cases for different item categories, each equally likely (25%)
   // ****** CASE 0: ores/resources
  // Reset base_chance for start of new case
  reset_base(0.25);
  if ($score>=5) {
    add_chance(386,0.03);
  }
  // If not iridium: game code creates a list with
  // * only one of gold, iron, copper, wood, stone
  // * coal
  // Game code then randomly selects one of the items in list.
  // Equivalent to 50% chance of coal being done initially, then falling
  // through to other possible items
  add_chance(382,0.5);
  if ($score>=4) {
    // Chance of gold is 100%:
    // (i.e., noitem_chance ends up as 0, so none of other resources actually possible)
    add_chance(384,1);
  }
  if ($score>=3)
    add_chance(380,0.6);
  // Each subsequent resource only added if possibles.count==0
  // add_chance function tracks how noitem_chance goes down with each subsequent item
  add_chance(378,0.6);
  add_chance(388,0.6);
  add_chance(390,0.6);
  // If all above fail, fallback is 382 (coal)
  // (Handled in game code by fact that coal always added to list of possible items)
  add_chance(382);
   // ****** CASE 1: spinner, bait
  // Reset base_chance for start of new case
  reset_base(0.25);
  if ($score>=4 && $skill>=6)
    add_chance(687,0.1);
  if ($wild_bait)
    add_chance(774,0.25);
  // Number of standard bait is level dependent; irrelevant since not tracking item number
  add_chance(685);
   // ****** CASE 2: artifacts
  // Reset base_chance for start of new case
  reset_base(0.25);
  if ($book)
    add_chance(102,0.1);
  if ($artifact) {
    if ($skill>1)
      add_chance('585-588',0.25);
    if ($skill>1)
      add_chance('103-120',0.5);
    add_chance(535);
  }
  add_chance(382);
   // ****** CASE 3: contains three equally likely sub-cases, so each section has 0.25 / 3 chance of happening
  // ****** Case 3 / Case 0: geodes
  reset_base(0.25 / 3);
  if ($score>=4) {
    add_chance(array(535,536),0.4);
    add_chance(537);
  }
  if ($score>=3) {
    add_chance(535,0.4);
    add_chance(536);
  }
  // Fallback to standard geode; only relevant if score<3 (otherwise $noitem_chance is 0 here)
  add_chance(535);
   // ****** Case 3 / Case 1 : gems
  reset_base(0.25 / 3);
  if ($skill<2) {
    add_chance(382);
  }
  else {
    if ($score>=4) {
      add_chance(82,0.3);
      add_chance(array(64,60));
    }
    if ($score>=3) {
      add_chance(84,0.3);
      add_chance(array(70,62));
    }
    add_chance(86,0.3);
    add_chance(array(66,68));
     // Diamonds are added in addition to primary gem ... so use base_chance instead of noitem_chance
    add_chance(72,0.028*$score/5, array('ignore_noitem'=>TRUE));
  }
   // ****** Case 3 / Case 2 : special items
  //
  // BIG difference in this section is that game code does NOT use elseif at all -- i.e., each special
  // item can be added regardless of whether earlier special items were already added.
  // Handled via ignore_noitem option in all calls to add_chance.
  reset_base(0.25 / 3);
  if ($skill<2) {
    add_chance(770);
  }
  else {
    // luckModifier variable alters all chances here
    $luckmod = max(0, (1 + $daily_luck * $score/5));
     // Both weapons also check that user doesn't have in inventory.  Ignoring that requirement
    // (and therefore its minor impact on the complicated diamond / fallback-to-bait)
    add_chance('w14',0.05*$luckmod, array('ignore_noitem'=>TRUE));
    add_chance('w51',0.05*$luckmod, array('ignore_noitem'=>TRUE));
     // NOTE: rings appear here in openTreasureMEnudEndFunction -- but I've moved them all to end
    // of session so all complications can be handled together
     add_chance(166,0.02*$luckmod, array('ignore_noitem'=>TRUE));
    if ($skill>5)
      add_chance(74,0.001*$luckmod, array('ignore_noitem'=>TRUE));
    add_chance(127,0.01*$luckmod, array('ignore_noitem'=>TRUE));
    add_chance(126,0.01*$luckmod, array('ignore_noitem'=>TRUE));
    add_chance(527,0.01*$luckmod, array('ignore_noitem'=>TRUE));
    add_chance('b504-b514',0.01*$luckmod, array('ignore_noitem'=>TRUE));
     // RINGS: moved here from above
    // All these rings are handled with a single 0.07 chance check for any ring -- then decision is made
    // about which ring is the one ring added.
    // Given that ignore_noitem is being done, this detail doesn't have any effect on chance for each
    // ring.  But it does have an effect on noitem_chance, oneitem_chance.
    //
    // So: do one dummy call to add_chance just to update noitem_chance, oneitem_chance
    $ring_chance = 0.07*$luckmod;
    add_chance(NULL, $ring_chance, array('ignore_noitem'=>TRUE));
    // Store values of those variables for use below with diamond, fallback-bait
    $special_oneitem_chance = $oneitem_chance;
    $special_noitem_chance = $noitem_chance;
     // Add all the rings
    // One-third chance: glow ring or small glow ring
    add_chance(517,$ring_chance/3 * ($luck_level/11), array('ignore_noitem'=>TRUE));
    add_chance(516,$ring_chance/3 * (1-$luck_level/11), array('ignore_noitem'=>TRUE));
    // One-third chance: magnet ring or small magnet ring
    add_chance(519,$ring_chance/3 * ($luck_level/11), array('ignore_noitem'=>TRUE));
    add_chance(518,$ring_chance/3 * (1-$luck_level/11), array('ignore_noitem'=>TRUE));
    // One-third chance: one of the other rings
    add_chance('529-535',$ring_chance/3, array('ignore_noitem'=>TRUE));
     // Add a diamond -- BUT ONLY IF treasures.count==1.  SUPER UGLY
    //
    // At first I thought this was a fallback option, i.e., happens only if treasures.count==0, in which
    // case it could be handled (largely) same way as fallbacks in every other section.
    //
    // But handling treasures.count==1 is actually much more complicated. Especially given that:
    // * rice shoots and wild bait contribute to treasures
    // * any previous loops contribute to treasures
    //
    // Getting every detail exactly right here is probably overkill, but it's hard to confidently
    // estimate which details matter until those details are all worked out.
     // First, combine net chances of a special item with the net chances of a pre-switch item.
    $oneitem_chance = $special_oneitem_chance*$prelim_noitem_chance + $special_noitem_chance*$prelim_oneitem_chance;
    $noitem_chance = $special_noitem_chance*$prelim_noitem_chance;
     // Primary source of diamond: added on first loop (but only first loop) if exactly one of
    // rice shoot / wild bait / all-special-items.
    $diamond_chance = $base_chance*$oneitem_chance;
     // Diamond also possible on second loop -- originally I thougt this would be minor
    // contributor, but turns out to be similar magnitude.  (Because prev_loop close
    // to 1; so key comparison ends up being oneitem_chance vs noitem_chance*0.4 --
    // which are pretty similar)
    // Necessary conditions:
    // * exactly one item from first loop (not just from special-items section, but any section)
    // * then on second loop, add zero items and do special-items section
     // Sum all ways that exactly one item is possible in previous loop
    // * All non-special-items section add one and only one item
    $prev_loop_oneitem = 1 - $base_chance;
    // * EXCEPT not if gems triggered a bonus diamond
    if (!empty($chances[72]))
      $prev_loop_oneitem -= $chances[72];
    // * Add chance that special-item-section fell through to bait
    //   (otherwise special-item-section never ends up with exactly one item ... because diamond turns one-item case into two-items)
    $prev_loop_oneitem += $special_noitem_chance*$base_chance;
    // Plus can't have any prelim items during first loop
    $prev_loop_oneitem *= $prelim_noitem_chance;
     // Then, on second loop get here with no items
    $second_loop_noitem = $base_chance * $noitem_chance;
     // Combine those two requirements PLUS 0.4 chance to even reach second loop
    $diamond_chance += $prev_loop_oneitem * $second_loop_noitem * 0.4;
     // Adding manually because all factors such as base_chance, etc. have already been taken into account -- and
    // this doesn't have any after-the-fact implications
    $fixed_chances[72] = $diamond_chance;
   }
  // Final fallback: Bait
  // Only if treasures.count==0
  // In game code, this line code of code appears after all switches, and technically applies to any category.
  // But in reality, the only category that can possibly not have already added an item is the special items
  // AND only on first loop.
  //
  // Because the test is based on treasures.count, it needs to use the cumulative noitem_chance calculated above
  // from special and prelim values (which was already stored to standard noitem_chance variable)
  // (And adding a dimond doesn't affect this check, because it always changes treasures.count from 1 to 2)
   // Using first_loop_only option stores result in $fixed_chances
  add_chance(685, 1, array('first_loop_only'=>TRUE));
   return $chances;
}
 // Integrate chance from a single loop over multiple loops, with 40% chance of repeating on each loop
function integrate_loops($single_loop_chance, $options=array()) {
  $repeat_chance = 0.4;
   $total_chance = 0;
  $active = 1;
  for ($i=0; $i<20; $i++) {
    $curr_chance = $active*$single_loop_chance;
    $total_chance += $curr_chance;
    $start_of_loop = $active *= (1-$single_loop_chance);
    $active *= 0.4;
  }
  return $total_chance;
}
 // Set base_chance at start of new section, and reset tracking variables
function reset_base($new_base_chance) {
  global $base_chance, $noitem_chance, $oneitem_chance;
   $base_chance = $new_base_chance;
  $noitem_chance = 1;
  $oneitem_chance = 0;
}
 /*
 * Function to handle basic mechanics of tracking each item's chance
 *
 * Arguments:
 *  $item_ids: item(s) to process. Can be a single item_id, an array of
 *    multiple item_ids, or a string specifying a range of item_ids.
 *  $raw_item_chance: chance that these item_ids will be added to treasure chest.
 *    If multiple item_ids used, all are assumed to be equally likely and
 *    $raw_item_chance is split equally among them.
 * $options: $options[ignore_noitem] should be set to true if these items
 *    are added in chest in addition to any other items.
 *
 * The intention here is that $raw_item_chance is normally the same value that
 * appears in the game code (in openTreasureMenuEndFunction).  This
 * $raw_item_chance then needs to be multiplied by the chance of reaching
 * that point in the code to get the actual chance of the item appearing --
 * which is a combination of base_chance (the chance of that category/
 * switch-case) and, in most cases, noitem_chance.
 */
function add_chance($item_ids, $raw_item_chance=1, $options=array()) {
  global $chances, $fixed_chances;
  global $base_chance, $noitem_chance, $oneitem_chance;
  global $item_list;
   if (empty($options['ignore_noitem'])) {
    // Since most items in the list are mututally exclusive (only one item
    // normally gets added to treasure chest), normally an item is only
    // added if all previously-tested items failed to be added.  Therefore,
    // $raw_item_chance is normally multiplied by $base_chance*$noitem_chance
    $subset_chance = $noitem_chance;
  }
  else {
    // If explicitly requested by the ignore_noitem option, don't fold in
    // noitem_chance.
    // This is only appropriate when this item is added regardless of whether
    // another item was already added -- primarily for special items.
    $subset_chance = 1;
  }
   $final_item_chance = $raw_item_chance * $subset_chance * $base_chance;
   // Update noitem_chance in preparation for next possible item.
  //
  // This calculation is same whether or not ignore_noitem is set.
  // When ignore_noitem is set, the final_item_chance is higher because
  // some chests that already one (or more) items add another -- but the
  // fraction of no-item chests that add an item is the same.
  $orig_noitem_chance = $noitem_chance;
  $noitem_chance = $orig_noitem_chance*(1-$raw_item_chance);
   // Plus track oneitem_chance -- chance of one and only one item being found so far.
  // This value is only used in ignore_noitem case.
  if (!empty($options['ignore_noitem'])) {
    // One item possible either:
    // * previously noitem cases that add one from $item_ids
    // * previously oneitem cases that don't add any of $item_ids
    $oneitem_chance = $orig_noitem_chance*$raw_item_chance + $oneitem_chance*(1-$raw_item_chance);
  }
   if (!$final_item_chance)
    return;
   // Convert range of item_ids into list of individual item_ids
  if (is_string($item_ids) && strpos($item_ids,'-')!==FALSE) {
    list($min_id,$max_id) = explode('-',$item_ids);
    $item_ids = array();
    $prefix = '';
    // handling range same way as random.next -- min_id allowed, max_id is NOT
    if (!ctype_digit($min_id)) {
      $prefix = substr($min_id,0,1);
      $min_id = substr($min_id,1);
    }
    if (!ctype_digit($max_id)) {
      $prefix = substr($max_id,0,1);
      $max_id = substr($max_id,1);
    }
    for ($item_id=$min_id; $item_id<$max_id; $item_id++)
      $item_ids[] = $prefix.$item_id;
  }
  elseif (!is_array($item_ids))
    $item_ids = array($item_ids);
   $nitems = count($item_ids);
  foreach ($item_ids as $item_id) {
    // skip placeholder items
    if (empty($item_id))
      continue;
    if (!empty($options['first_loop_only'])) {
      @ $fixed_chances[$item_id] += $final_item_chance / $nitems;
    }
    else {
      @ $chances[$item_id] += $final_item_chance / $nitems;
    }
  }
}
