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

	init(lang) {

		if (this.lang === lang)	{
			return true;
		}	

		this.lang = lang;
		var thisVar = this;

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
		
		if ($("#hl-main-div").length) {
	
			var overlayHTML = "<table id='hl-overlay'><tbody><tr><td style='vertical-align: middle; text-align: center;'></td></tr></tbody></table>";
			var waitDIV = "<div id='hl-wait-div'><img style='width: 50%' src='" + this.#getScriptHomeURL() + "images/wait.gif'><div style='vertical-align: top; text-align: center;'><a href='javascript:hlObj.close()'><span style='color: white;'><big><b>" + this.local.cancelAndGoBack + "</b></big></span></a></div></div>";
			$('body').append(overlayHTML);
			$('body').append(waitDIV);
					
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
					if (!thisVar.settings.emailLoginEnabled) {
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
		
}
