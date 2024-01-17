var hlObj = null;

document.addEventListener('DOMContentLoaded', function() {
	hlObj = new hybridLogin();
});

class hybridLogin {

	constructor() {

		this.chkConnectionInProgress = false;
		this.waitForConnectionInterval = null;
		this.callbackWindow = null;
		this.callback = null;
		this.scriptHomeURL = this.#getScriptHomeURL();
		this.settings = null;
		this.lang = '';
		this.local = null;

	}

	initRegister() {

		if ($("#hl-main-div").length) {

			var templatesImagesURL = this.scriptHomeURL + "templates/images/";	
			var thisVar = this;
			
			$.ajax({
				url: this.scriptHomeURL + "templates/" + this.settings.registerTemplate + "_" + this.lang + ".html?ver=" + Date.now().toString,
				async: true,
				success: function(data) {
					data = data.replace(/images\//g, templatesImagesURL);
					$("#hl-main-div").html(data);
					
					if ($("#close-button").length) {
						$("#close-button").click(function() {
							hlObj.closeRegister();
						});						
					}

					if ($("#hl-undo-registration").length) {
						$("#hl-undo-registration").click(function() {
							hlObj.init('');
						});						
					}

					if ($("#hl-privacy").length && thisVar.settings.hasOwnProperty('privacyPageUrl')) {
						$("#hl-privacy").prop('href', thisVar.settings.privacyPageUrl);
					}

					$($("#hl-resgister-btn")).click(function() {
						hlObj.register();
					});						

				}
			});
		}
	
	}

	init(lang) {

		if (this.lang === lang)	{
			return true;
		}	

		if (lang.trim()) {
			this.lang = lang;
		}

		var thisVar = this;
		
		if ($("#hl-main-div").length) {

			if (!$("#hl-overlay").length) {
				$.ajax({
					url: this.scriptHomeURL,
					type: 'POST',
					data: {cmd: "settings"},
					dataType: 'json',
					async: false,
					success: function(data) {
						thisVar.settings = data;
					}
				});		
				$.ajax({
					url: this.scriptHomeURL,
					type: 'POST',
					data: {cmd: "local", lang: lang},
					dataType: 'json',
					async: false,
					success: function(data) {
						thisVar.local = data;
					}
				});			
				var overlayHTML = "<table id='hl-overlay'><tbody><tr><td style='vertical-align: middle; text-align: center;'></td></tr></tbody></table>";
				var waitDIV = "<div id='hl-wait-div'><img style='width: 50%' src='" + this.#getScriptHomeURL() + "images/wait.gif'><div style='vertical-align: top; text-align: center;'><button id='hl-undo'  style='background-color: red;' onclick='hlObj.close()' type='button'><span id='hl-undo-text' style='color: white;'><big><b>" + this.local.cancelAndGoBack + "</b></big></span></button></div></div>";
				$('body').append(overlayHTML);
				$('body').append(waitDIV);
			}

			var templatesImagesURL = this.scriptHomeURL + "templates/images/";	
			var thisVar = this;
			
			$.ajax({
				url: this.scriptHomeURL + "templates/" + this.settings.template + "_" + this.lang + ".html?ver=" + Date.now().toString,
				async: true,
				success: function(data) {
					data = data.replace(/images\//g, templatesImagesURL);
					$("#hl-main-div").html(data);
					$("#hl-wait-div").css({
						"width": "24em",
						"height": "24em",
						"left": "calc(50% - 12em)",
						"top": "calc(50% - 12em)",
						"text-align": "center"
					});
					if (thisVar.settings.emailLoginEnabled) {
						if ($("#hl-email-login").length) {
							$("#hl-email-login").click(function() {
								hlObj.emailLogin();
							});						
						}	
					} else {
						$(".hl-email").hide();
					}
					$(".hl-social").hide();
					if (thisVar.settings.providers.length == 0) {
						$(".hl-social-buttons").hide();
					} else {
						thisVar.settings.providers.forEach(function(provider) {
							var providerName = provider;
							provider = provider.toLowerCase();
							$(".hl-" + provider).show();
							var providerID = "#hl-" + provider + "-connect";
							if ($(providerID).length) {
								$(providerID).click(function() {
									hlObj.connect(providerName);
								});						
							}
						});
					}
					if ($("#close-button").length) {
						$("#close-button").click(function() {
							hlObj.close();
						});						
					}
					if ($("#hl-register").length) {
						$("#hl-register").prop('href', 'javascript:hlObj.initRegister()');
					}
				}
			});
		}
	
	}

	start(callback = null, test = false) {

		this.callback = callback;

		if (test) {
			this.chkConnection('');
		} else {
			$("#hl-overlay").show();
			$("#hl-main-div").show();
		}

	}

	connect(provName) {

		$("#hl-main-div").hide();
		$("#hl-wait-div").show();
		this.callbackWindow = window.open(this.scriptHomeURL + '?provider=' + provName, provName + ' Login', 'width=500,height=500');
		var thisVar = this;
		this.waitForConnectionInterval = setInterval(function() {
			thisVar.chkConnection(provName);			
		}, 1000);

	}

	close() {

		$("#hl-main-div").hide();
		$("#hl-wait-div").hide();
		$("#hl-overlay").hide();

		if (this.waitForConnectionInterval) {
			clearInterval(this.waitForConnectionInterval);
			this.waitForConnectionInterval = null;
		}

		if (this.callbackWindow) {
			try {
				this.callbackWindow.close();
			}
			catch(err) {

			} 
			finally {
				this.callbackWindow = null;
			}
		}

	}

	closeRegister() {

		this.close();
		this.init('');

	}

	#cutAfterLastSlash(str) {
	
		var lastSlashIndex = str.lastIndexOf('/');
		if (lastSlashIndex === -1) return str;
		return str.substring(0, lastSlashIndex + 1);
		
	}
	
	#getScriptHomeURL() {
	
		var result = null;
		var scripts = document.getElementsByTagName('script');
		
		for (var i = 0; i < scripts.length; i++) {
			if (scripts[i].src.includes("hybridlogin.js")) {
				result = scripts[i].src;
				break;
			}
		}
		
		if (result) {
			result = this.#cutAfterLastSlash(result);				
		}
		
		return result;
		
	}

	chkConnection(provName) {

		if (this.chkConnectionInProgress) {
			return false;
		} else {
			this.chkConnectionInProgress = true;
		}

		try {
			var err = null;
			var cookieData = Cookies.get(this.settings.app);
			if (cookieData) {
				var items = cookieData.split('|');
				var email = items[0];
				var accountType = items[1];
				var password = items.slice(2).join();
				var thisVar = this;
				if ((!provName) || (accountType.toUpperCase() === provName.toUpperCase())) {
					if (!provName) {
						cookieData = null;
					}
					$.ajax({
						url: this.scriptHomeURL,
						type: 'POST',
						data: {cmd: "chkConnection", email: email, accountType: accountType, password: password},
						dataType: 'json',
						async: false,
						success: function(data) {
							if (data.connected) {
								thisVar.close();
								setTimeout(thisVar.callback(email, accountType, password, err), 500);	
							} else {
								thisVar.disconnect();
							}
							thisVar.chkConnectionInProgress = false;
						}
					});
				} else {
					cookieData = null;
				}
			} else {
				email = null;
				accountType = null;
				password = null;
			}
			if ((!cookieData) && (!this.callbackWindow) && this.callback) {
				this.callback(email, accountType, password, err);
			}			
		} finally {
			this.chkConnectionInProgress = false;
		}

	}

	disconnect() {

		Cookies.remove(this.settings.app)

		if ((!this.callbackWindow) && this.callback) {
			this.callback(null, null, null, null);
		}

	}	

	errorsOutput(errors) {

		var errDiv = $("#hl-errors");

		if (errDiv.length) {
			if (errors.length) {
				var html = this.local.errorSectionTitle + '<ul class="hl-err-ul">';
				for (let error of errors) {
					html += '<li class="hl-err-item">' + error + '</li>'
				}
				html += '</ul>'
				errDiv.html(html);
				errDiv.show();
			} else {
				errDiv.hide();
			}
		}

	}

	register() {

		var email = $('#hl-email').val().trim();
		var password = $('#hl-password').val().trim();
		var thisVar = this;

		$.ajax({
			url: this.scriptHomeURL,
			type: 'POST',
			data: {cmd: "register", email: email, pwd: password, lang: thisVar.lang},
			dataType: 'json',
			async: true,
			beforeSend: function(xhr) {
				$("#hl-main-div").hide();
				$("#hl-wait-div").show();
			},
			success: function(data) {
				if (data.succeeded) {
					alert(thisVar.local.registrationSucceeded);
					thisVar.init('');
					$("#hl-main-div").show();
					//setTimeout(thisVar.callback(email, 'EMAIL', data.password, null), 500);	
				} else {
					thisVar.errorsOutput(data.err)
					$("#hl-main-div").show();
				}
			},
			complete: function(xhr, status) {
				$("#hl-wait-div").hide();
			}
		});		

	}

	emailLogin() {

		var email = $('#hl-email-field').val().trim();
		var password = $('#hl-password-field').val().trim();
		var remember = false;
		if ($("#hl-remember-me-field").length) {
			remember = $("#hl-remember-me-field").prop('checked');
		}
		var thisVar = this;
		$.ajax({
			url: this.scriptHomeURL,
			type: 'POST',
			data: {cmd: "emailLogin", email: email, pwd: password, remember: remember},
			dataType: 'json',
			async: true,
			beforeSend: function(xhr) {
				$("#hl-main-div").hide();
				$("#hl-wait-div").show();
			},
			success: function(data) {
				if (data.succeeded) {
					thisVar.close();
					setTimeout(thisVar.callback(email, 'EMAIL', data.password, null), 500);	
				} else {
					alert(thisVar.local.loginFailed);
					$("#hl-main-div").show();
					$('#hl-email-field').focus();
				}
			},
			complete: function(xhr, status) {
				$("#hl-wait-div").hide();
			}
		});		

	}

}
