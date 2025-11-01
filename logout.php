<?php
//ourSiteLogOutPage

//Include session management
require_once 'includes/session.php';

// Clear the session
clearUserSession();

//Set a logout message
session_start();
setMessage('You have been logged out successfully.', 'success');

//  Redirect to home page
header('Location: index.php');
exit;
?>
