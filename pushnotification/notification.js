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
            apiKey: "AIzaSyDKTB0oNvv_SjwcbsbVfKucLVBs0SuENv8",
            authDomain: "moodle-app-80674.firebaseapp.com",
            databaseURL: "https://moodle-app-80674.firebaseio.com",
            projectId: "moodle-app-80674",
            storageBucket: "moodle-app-80674.appspot.com",
            messagingSenderId: "448137247676"
        };

        firebase.initializeApp(config);
        const messaging = firebase.messaging();
        messaging.requestPermission().then(function () {
            return messaging.getToken();
        }).then(function (token) {
            console.log('Firebase token: ', token);
            var  BL_URL =  'https://stgbl.fliplearn.com';
            var  headers =  {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'SupportedApiVersion': 1,
                    'platform': 'web',
                };
            var domainName = document.location.hostname;
            var deviceInfo = {
                "device": {
                    "actionedBy": "1234",
                    "appVersion": 1,
                    "platform": window.navigator.platform,
                    "osVersion": window.navigator.appVersion,
                    "osName": "web",
                    "serialNo": token,
                    "arnToken": '',
                    "Imei": token,
                    "MacAddress": "",
                    "DeviceModel": "",
                    "domainName":domainName,
                }
            }
            $.ajax({
                    type: "POST",
                    headers: headers,
                    url: BL_URL+"/user/registerDevice",
                    data: deviceInfo,
                    success: function(result){
                        console.log('data')
                        console.log(result);
                    }
                });
            localStorage.setItem("WEB_FIREBASE_ARN_TOKEN", token);
        }).catch(function (err) {
            console.log('Permission denied', err);
        });
        messaging.onMessage(function (payload) {
            console.log('onMessage: ', payload);
        var n = new Notification(payload.notification.title,payload);
        });