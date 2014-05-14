<?php 
$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
	    'id'=>'acceptTermsForFacebookLoginWindow',
	    // additional javascript options for the dialog plugin
	    'options'=>array(
	        'title'=>Yii::t('site', 'Accept our Terms to continue'),
	        'autoOpen'=>false,
	        'modal'=>true, 
			'resizable'=>false,
			'width'=> '600px',
	    	//'close' => 'js:function(){ showFormErrorsIfExist(); }'
	    ),
	));
?>
	<div class="row" style="padding-top:2em;padding-bottom:1em;">
		<?php 
		echo Yii::t('site', 'We have updated our {terms of use} as of October 28, 2013. By continuing to log in, you agree to our Terms of Use.', array('{terms of use}'=>
				CHtml::ajaxLink(Yii::t('layout', 'Terms of Use'), $this->createUrl('site/terms'),
						array(
								'complete'=> 'function() { $("#termsWindow").dialog("open"); return false;}',
								'update'=> '#termsWindow',
						),
						array(
								'id'=>'showTermsWindowForFacebookLoginContinue','tabindex'=>15,'style'=>'color:#6261D8;'))
		));
		?>
	</div>	
	
	</br>	

	<div class="row buttons" style="padding-bottom:1em;">
		<?php
// 		$this->widget('zii.widgets.jui.CJuiButton', array(
// 				'name'=>'ajaxContinueLoginButton',
// 				'caption'=>Yii::t('site', 'I accept, continue'),
// 				'id'=>'continueLoginAjaxButton-'.uniqid(), //Unique ID oluşturmayınca her ajaxta bir önceki sorgular da tekrarlanıyor
// 				'htmlOptions'=>array('type'=>'submit','ajax'=>array(/*'type'=>'POST',*/'url'=>$this->createUrl('site/continueLogin', array('LoginForm'=>$form)), 'complete'=> 'function() { $("#acceptTermsForLoginWindow").dialog("close");}', 'update'=>'#acceptTermsForLoginWindow'
// 				))
// 		));

// 		$this->widget('zii.widgets.jui.CJuiButton', array(
// 				'name'=>'ajaxContinueLoginButton',
// 				'caption'=>Yii::t('site', 'I accept, continue'),
// 				'id'=>'continueLoginAjaxButton-'.uniqid(), //Unique ID oluşturmayınca her ajaxta bir önceki sorgular da tekrarlanıyor
// 				'htmlOptions'=>array('type'=>'submit',
// 									 'ajax'=>array('url'=>$this->createUrl('site/continueLogin', array('LoginForm'=>$form)),
// 												   'success'=>'function(msg){
// 																try
// 																{
// 																	var obj = jQuery.parseJSON(msg);
												
// 																	if (obj.result)
// 																	{
// 																		if (obj.result == "1")
// 																		{
// 																			$("#acceptTermsForLoginWindow").dialog("close");
// 									 										$("#tabViewList").html(obj.renderedTabView);
// 																			$("#loginBlock").html(obj.loginSuccessfulActions);
// 																		}
// 																	}
// 																}
// 																catch (error)
// 																{
// 																	$("#loginBlock").html(msg);
// 																}
// 															}',
// 				))
// 		));

		$app = Yii::app();
			
		echo CHtml::ajaxLink('<button class="btn btn-sliding-green btn-sliding-green-a icon-arrow-right" style="'.(($app->language == 'en')?'padding-left:28px;padding-right:28px;':'padding-left:28px;padding-right:28px;').'">'.'<span style="font-family:Helvetica">'.Yii::t('site', 'I accept, continue').'</span>'.'</button>', $this->createUrl('site/continueFacebookLogin', array()),
				array(
						'type'=>'POST',
						'success'=>'function(msg){
										try
										{
											var obj = jQuery.parseJSON(msg);
										
											if (obj.result)
											{
												if (obj.result == "1")
												{
													$("#acceptTermsForFacebookLoginWindow").dialog("close");
						
													$("#tabViewList").html(obj.renderedTabView);
													$("#userarea").html(obj.renderedUserAreaView);
													$("#FriendRequestsIconLink").html(obj.renderedFriendshipRequestsView);
													$("#loginBlock").html(obj.loginSuccessfulActions);
												}
												else if (obj.result == "0")
												{
													$("#acceptTermsForFacebookLoginWindow").dialog("close");
													TRACKER.showMessageDialog("'.Yii::t('site', 'An error occured during login. Please retry the process and if the error persists please contact us.').'");
												}
												else if (obj.result == "-1")
												{
													$("#acceptTermsForFacebookLoginWindow").dialog("close");
													TRACKER.showMessageDialog("'.Yii::t('site', 'An error occured during register process. Please retry the process and if the error persists please contact us.').'");
												}
												else if (obj.result == "-2")
												{
													$("#acceptTermsForFacebookLoginWindow").dialog("close");
						
													var opt = {
														autoOpen: false,
														modal: true,
														resizable: false,
														width: 600,
														title: "'.Yii::t('site', 'Enter Your Traceper Password to Log In').'"
													};
													
													$("#enterPasswordForOldFacebookUserToLoginWindow").dialog(opt).dialog("open");
													$("#enterPasswordForOldFacebookUserToLoginWindow").html(obj.renderedView); 
												}						
												else if (obj.result == "-4")
												{
													$("#acceptTermsForFacebookLoginWindow").dialog("close");
						
													var opt = {
														autoOpen: false,
														modal: true,
														resizable: false,
														width: 600,
														title: "'.Yii::t('site', 'Do you want to switch to Facebook login permanently?').'"
													};
													
													$("#askForSwitchToFacebookLoginPermanentlyWindow").dialog(opt).dialog("open");
													$("#askForSwitchToFacebookLoginPermanentlyWindow").html(obj.renderedView); 
												}						
												else
												{
													$("#acceptTermsForFacebookLoginWindow").dialog("close");
													TRACKER.showMessageDialog("'.Yii::t('site', 'An error occured during login. Please retry the process and if the error persists please contact us.').'");
												}																		
											}
										}
										catch (error)
										{
											alertMsg("acceptTermsForFacebookLogin - error occured");
										}
									}',
				),
				array('id'=>'continueLoginAjaxButton-'.uniqid(), 'style'=>'padding-right:4px;'));		
		?>
											
		<?php			 
// 			$this->widget('zii.widgets.jui.CJuiButton', array(
// 					'name'=>'cancelLogin',
// 					'caption'=>Yii::t('common', 'Cancel'),
// 					'id'=>'cancelLoginButton',
// 					'onclick'=> 'js:function(){$("#acceptTermsForLoginWindow").dialog("close"); return false;}'
// 			));

			echo CHtml::ajaxLink('<button class="btn btn-sliding-red btn-sliding-red-a icon-close" style="'.(($app->language == 'en')?'padding-left:25px;padding-right:25px;':'padding-left:28px;padding-right:28px;').'">'.'<span style="font-family:Helvetica">'.Yii::t('common', 'Cancel').'</span>'.'</button>', '#',
					array(),
					array('id'=>'cancelLoginButton', 'onclick'=>'$("#acceptTermsForFacebookLoginWindow").dialog("close"); return false;'));			
		?>												
	</div>
<?php 
	$this->endWidget('zii.widgets.jui.CJuiDialog');
?>