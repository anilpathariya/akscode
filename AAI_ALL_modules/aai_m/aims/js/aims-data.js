var app = angular.module('myApp', []);


app.controller('myCtrln', function($scope, $http) {
    $http.get("welcome.htm")
    .then(function(response) {
        $scope.myWelcome = response.data;
    });
});

app.controller('myCtrlnew', function($scope, $http) {
    $http.get("welcome2.html")
    .then(function(response) {
        $scope.myWelcomenew = response.data;
    });
});
 

 






app.controller('myCtrlns', ['$scope', '$http', '$sce',
    function ($scope, $http, $sce) {  
$( "#dataa22" ).load( "/investors/showlivedatal/international" );
	
    $scope.myFunc = function() {		
		$http.get("/investors/showlivedatal/international")
    .then(function(response) {
		
        $scope.livedata = response.data;
    });
		
		
    };		 
}]); 

app.controller('myCtrlds', ['$scope', '$http', '$sce',
    function ($scope, $http, $sce) {  
$( "#dataa23" ).load( "/investors/showlivedatal/domestic" );
	
    $scope.myFunc = function() {		
		$http.get("/investors/showlivedatal/domestic")
    .then(function(response) {
		
        $scope.livedata = response.data;
    });
		
		
    };		 
}]); 

app.controller('myCtrlcs', ['$scope', '$http', '$sce',
    function ($scope, $http, $sce) {  
	
$( "#dataa24" ).load( "/investors/showlivedatal/cargo" );
 
    $scope.myFunc = function() {		
		$http.get("/investors/showlivedatal/cargo")
    .then(function(response) {
		
        $scope.livedata = response.data;
    });
		
		
    };		 
}]); 