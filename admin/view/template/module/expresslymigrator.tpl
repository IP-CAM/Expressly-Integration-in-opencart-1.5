<?php echo $header; ?>

<script>
	var baseUrl = "<?php echo $base; ?>";
	var modulePass = "<?php echo $modulePass; ?>";
</script>

<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  
	<?php if ($fail) { ?>
		<div class="warning"><?php echo $fail; ?></div>
	<?php } ?>
	
	<?php if ($success) { ?>
		<div class="success"><?php echo $success; ?></div>
	<?php } ?>
	
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
    </div>
    
    <div class="content">
   		<img id="expresslyLogo" src="view/image/Logo_Blue_s.jpg" />
	    <table id="module" class="list">
          <thead>
            <tr>
              <td>General config</td>
            </tr>
          </thead>
          <tbody id="module-row">
            <tr>
              <td>
				<input type="checkbox" name="redirect-to-checkout" onclick="updateRedirectToCheckout(this, '<?php echo $token; ?>')" <?php if($redirectToCheckout == true) { ?>checked="checked"<?php } ?> /> Redirect incoming customers to checkout
	            <br />
	            <input type="checkbox" name="post-checkout-box" onclick="updatePostCheckoutBox(this)" <?php if($postCheckoutBox == true) { ?>checked="checked"<?php } ?> disabled="disabled" /> Include promo recommendation on order page
	            <br />
	            <input type="checkbox" name="redirect-to-login" onclick="updateRedirectToLogin(this)" <?php if($redirectToLogin == true) { ?>checked="checked"<?php } ?> /> Redirect existing customers to login
              </td>
            </tr>
          </tbody>
        </table>
        
        <table id="module" class="list">
          <thead>
            <tr>
              <td>Endpoint check</td>
            </tr>
          </thead>
          <tbody id="module-row">
            <tr>
              <td>
		            <a href="JavaScript:runEndpointTests()"><div id="endpointCheckLauncher">Launch endpoint check</div></a>
		            <br /><br />
		            <span class="underlineText">Self endpoints</span><br /><br />
		            <div class="selfCheckStepName"><span class="modulecheckstepname" id="checkStep1">User&nbsp;creation</span></div><div class="selfCheckStepResult"><span class="modulechecstep_1_result"></span></div><div id="modulechecstep_1_howtofix"><span class="underlineText"><a href="JavaScript:void(0)" onclick="showHowToFixContent('modulechecstep_1_howtofix')">How to fix it</a></span><div class="howtofix_content">It is possible that the test user has been left in your system (or was previously present). Go to Customers -> Manage customers and find the user with endpoint.test@buyexpressly.com e-mail address, and delete it. If the problem is still occurs, please contact expressly.</div></div><br />
		            <div class="selfCheckStepName"><span class="modulecheckstepname" id="checkStep2">User&nbsp;information</span></div><div class="selfCheckStepResult"><span class="modulechecstep_2_result"></span></div><div id="modulechecstep_2_howtofix"><span class="underlineText"><a href="JavaScript:void(0)" onclick="showHowToFixContent('modulechecstep_2_howtofix')">How to fix it</a></span><div class="howtofix_content">It is possible that the test user has been left in your system (or was previously present). Go to Customers -> Manage customers and find the user with endpoint.test@buyexpressly.com e-mail address, and delete it. If the problem is still occurs, please contact expressly.</div></div><br />
		            <br /><br />
		            <span class="underlineText">External endpoint</span><br /><br />
		            <div class="selfCheckStepName"><span class="modulecheckstepname" id="checkStep5">Migration&nbsp;service&nbsp;availability</span></div><div class="selfCheckStepResult"><span class="modulechecstep_5_result"></span></div><div id="modulechecstep_5_howtofix"><span class="underlineText"><a href="JavaScript:void(0)" onclick="showHowToFixContent('modulechecstep_5_howtofix')">How to fix it</a></span><div class="howtofix_content">Please contact expressly</div></div><br />
		            
		            <div id="module_check_result"></div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <table id="module" class="list">
          <thead>
            <tr>
              <td>Campaign management</td>
            </tr>
          </thead>
          <tbody id="module-row">
            <tr>
              <td>
				<i>Self management coming soon</i> - contact us at campaigns@buyexpressly.com to setup a campaign
              </td>
            </tr>
          </tbody>
        </table>
        
        <table id="module" class="list">
          <thead>
            <tr>
              <td>Security</td>
            </tr>
          </thead>
          <tbody id="module-row">
            <tr>
              <td>
				<span class="underlineText">Change module password</span>
	            <br />
	            (this will prevent Expressly to connect to your store, if the password is not communicated back)
	            <br /><br />
	            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" onsubmit="return confirm('The new password will be sent to expressly')">
	                <input name="form_key" type="hidden" value="" />
	                <input class="expresslyLongInput" type="text" name="modulePass" value="<?php echo $modulePass; ?>" />
	                <br /><br />
	                <input class="expresslyLargeButton" type="submit" name="save" value="Save" />
	            </form>
              </td>
            </tr>
          </tbody>
        </table>
        
    </div>
  </div>
</div>
<?php echo $footer; ?>