<?php

require '../../../etc/Env.php'; // Require the environmental variables file

/* This .php file will be an idea I have had about a swap matchmaking tool.
 * I will have to organize the way to do it
 * Definitely I will have to import from other files a rank comparison tool (switch statement)
 * I'll also have to inherit a shift selector
 */

// Connect to the database

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {
    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

$sql_dump_swaps = "SELECT * FROM Swaps ORDER BY Day";
$result_swaps = mysqli_query($conn, $sql_dump_swaps);
$swaps_table_requests = [];
$swaps_table_offers = [];
while ($row_swaps_dump = mysqli_fetch_assoc($result_swaps)) {
    if (strpos($row_swaps_dump["Command"], "Request") === 0) {
        array_push($swaps_table_requests, $row_swaps_dump);
    } else {
        array_push($swaps_table_offers, $row_swaps_dump);
    }
}

/* Now the table swaps has been dumped into 2 arrays; one of them being the requests and the other one the offerings.
 * Now I need to find a way to compare them. I guess the first thing I am going to compare is the date, and from there we can work our way up 
 * onto more "complicated" filters, such as the shift and the rank compatibility.
 * 
 * What I'll do now is to recourse through the arrays and see if there are any dates matching.
 * If there are, we can then try and see if the swap could be done (rank and shift dependant). */

foreach ($swaps_table_requests as $swapr) {
    foreach ($swaps_table_offers as $swapo) {
        if ($swapo["Fecha"] === $swapr["Fecha"]) {
            // In theory this means the dates are equal, and so there is a request pending that match a swap offering
            // Let's filter out by shift and then rank
            switch (true) {
                case ($swapo["Shift"] === $swapr["Shift"]):
                    // This means the shift chosen is the same (either earlies or lates, so 2nd check has been completed succesfully)
                    break;
                case (($swapo["Shift"] === "Earlies") and ($swapr["Shift"] === "Lates")):
                    /* Here the swap offer is for an early shift and the request is for a late shift.
                     * Most likely this will be rejected due to insufficent rest.
                     * The only case where this COULD be viable is if the person is on their last day off before lates and doesn't mind doing a late shift */
                    continue 2;
                case (($swapo["Shift"] === "Lates") and ($swapr["Shift"] === "Earlies")):
                    /* This is the opposite of the previous case. Offer for a late and request on earlies.
                     * This could not be possible if the swap is on the first day off after lates.
                     * This COULD be viable, but of course it's always up to the preference of the offering crewmember.
                     * If they don't want an early shift, too bad for the one in need
                     * However, I'll offer the option to contact the crewmember offering the swap to see if they could do or not. */
                    continue 2;
            }
            
            // Let's define the list of ranks that are compatible and I save myself the hassle of having to rewrite the list multiple times
            $ju_compatible = ['JU (NEW)','JU','JU (AH)','JU (PU)'];
            $pu_compatible = ['JU (AH)','JU (PU)','PU (TC)','PU', 'PU (DS)','PU (BS)','PU (LC)','PU (TH. INS)','PU (INS)','PU (SEP)'];
            switch ($swapr["Rank"]) {
                /* Assuming the way I coded the shift checker works, all that's left is to see whether the ranks are compatible.
                 * The easiest way to do this I believe it is with a switch statement */
                case "JU (NEW)":
                case "JU":
                    if (in_array($swapo["rank"],$ju_compatible) === true) {
                        /* In theory, if we reach here it means that:
                         * 1. The date of the swap request and the swap offering are the same
                         * 2. The shift is compatible
                         * 3. The ranks are compatible */
                        
                        // Write code later here that will create the message to the person requesting the swap
                    }
            }
        }
    }
}









file_put_contents("SwapRequests.txt", print_r($swaps_table_requests, true));
file_put_contents("SwapOffers.txt", print_r($swaps_table_offers, true));
mysqli_close($conn);
?>