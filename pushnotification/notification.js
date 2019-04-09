 if ('serviceWorker' in navigator) {
            console.log('Service Worker and Push is supported');
            navigator.serviceWorker.register('/pushnotification/serviceworker.js')
                .then(function (registration) {
                    console.log('Service Worker is registered', registration);
                    
                })
                .catch(function (error) {
                    console.error('Service Worker Error', error);
                });
        } else {
            console.warn('Push messaging is not supported');
        }    
        // Initialize Firebase
        var config = {
            apiKey: "AIzaSyCbxoGEKR6q-lDg7p6VwRufgbx4z8Z-AAQ",
            authDomain: "pwa-live.firebaseapp.com",
            databaseURL: "https://pwa-live.firebaseio.com",
            projectId: "pwa-live",
            storageBucket: "pwa-live.appspot.com",
            messagingSenderId: "855378505035"
        };

           var deviceCode = localStorage.getItem("WEB_DEVICE_CODE");

            if (deviceCode == undefined || deviceCode == null || deviceCode.length == 0) {
                    var nav = window.navigator;
                    var screen = window.screen;
                    deviceCode = nav.mimeTypes.length;
                    deviceCode += nav.userAgent.replace(/\D+/g, '');
                    deviceCode += nav.plugins.length;
                    deviceCode += screen.height || '';
                    deviceCode += screen.width || '';
                    deviceCode += screen.pixelDepth || '';
                    localStorage.setItem("WEB_DEVICE_CODE", deviceCode);
                }

        console.log(deviceCode);
        firebase.initializeApp(config);
        const messaging = firebase.messaging();
        messaging.requestPermission().then(function () {
            return messaging.getToken();
        }).then(function (token) {
            console.log('Firebase token: ', token);
            var  BL_URL =  '';
            var  headers =  {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                };
            var domainName = document.location.hostname;
            var deviceInfo = {
                "device": {
                    "actionedBy": "1234",
                    "appVersion": 1,
                    "platform": window.navigator.platform,
                    "osVersion": window.navigator.appVersion,
                    "osName": "web",
                    "serialNo": deviceCode,
                    "arnToken": token,
                    "Imei": deviceCode,
                    "MacAddress": "",
                    "DeviceModel": "",
                    "domainName":domainName,
                }
            }         
            $.ajax({
                    type: "get",
                    url: "/pushnotification/notificationConfig.php",
                    success: function(result){
                                var jsonObj =  JSON.parse(result);
                                if(jsonObj.error == ''){
                                    BL_URL =  jsonObj.BL_URL ;
                                    deviceInfo.device.actionedBy = jsonObj.uuid;
                                    $.ajax({
                                        type: "POST",
                                        headers: headers,
                                        url: BL_URL+"/user/registerDevice",
                                        data: deviceInfo,
                                        success: function(result){
                                            var obj = JSON.parse(result);
                                            if(obj.error == null){
                                            var deviceCode = obj.device.deviceCode;
                                            if(deviceCode){
                                             $.ajax({
                                                    type: "get",
                                                    url:BL_URL+"/user/autologinByUuid/"+jsonObj.uuid+"?deviceCode="+deviceCode,
                                                    success: function(res){
                                                        console.log('res',res)
                                                        var objRes = JSON.parse(res);
                                                        var sessionToken = objRes.data.sessionToken;
                                                        var uuid = objRes.data.uuid;
                                                         var userDetails = {
                                                                        "sessionToken": sessionToken,
                                                                        "uuid": uuid,
                                                                    }
                                                              $.ajax({
                                                                    type: "POST",
                                                                    url: "/pushnotification/saveUserDetails.php",
                                                                    data: userDetails,
                                                                    success: function(response){
                                                                        console.log(response);
                                                                    }});
                                                        localStorage.setItem("sessionToken", sessionToken);
                                                        localStorage.setItem("uuid", uuid);
                                                    }
                                                });
                                         }}                            
                                        }
                                    });
                            }
                    }
                });
        localStorage.setItem("WEB_FIREBASE_ARN_TOKEN", token);
        }).catch(function (err) {
            console.log('Permission denied', err);
        });
        messaging.onMessage(function (payload) {
            console.log('onMessage: ', payload);
        //var n = new Notification(payload.notification.title,payload);

        Notification.requestPermission().then( function( permission )
                {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                try {
                        registrations[0].showNotification(payload.notification.title,payload);
                }catch(ex){
                console.log(ex);
                }
             });

            });

        });