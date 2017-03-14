/* 扩展ThinkPHP对象 */
(function(window, angular,$){
	/**
	 * 获取ThinkPHP基础配置
	 * @type {object}
	 */
	var ThinkPHP = window.Think;

	/* 基础对象检测 */
	ThinkPHP || $.error("ThinkPHP基础配置没有正确加载！");

	/**
	 * 解析URL
	 * @param  {string} url 被解析的URL
	 * @return {object}     解析后的数据
	 */
	ThinkPHP.parse_url = function(url){
		var parse = url.match(/^(?:([a-z]+):\/\/)?([\w-]+(?:\.[\w-]+)+)?(?::(\d+))?([\w-\/]+)?(?:\?((?:\w+=[^#&=\/]*)?(?:&\w+=[^#&=\/]*)*))?(?:#([\w-]+))?$/i);
		parse || $.error("url格式不正确！");
		return {
			"scheme"   : parse[1],
			"host"     : parse[2],
			"port"     : parse[3],
			"path"     : parse[4],
			"query"    : parse[5],
			"fragment" : parse[6]
		};
	}

	ThinkPHP.parse_str = function(str){
		var value = str.split("&"), vars = {}, param;
		for(val in value){
			param = value[val].split("=");
			vars[param[0]] = param[1];
		}
		return vars;
	}

	ThinkPHP.parse_name = function(name, type){
		if(type){
			/* 下划线转驼峰 */
			name.replace(/_([a-z])/g, function($0, $1){
				return $1.toUpperCase();
			});

			/* 首字母大写 */
			name.replace(/[a-z]/, function($0){
				return $0.toUpperCase();
			});
		} else {
			/* 大写字母转小写 */
			name = name.replace(/[A-Z]/g, function($0){
				return "_" + $0.toLowerCase();
			});

			/* 去掉首字符的下划线 */
			if(0 === name.indexOf("_")){
				name = name.substr(1);
			}
		}
		return name;
	}

	//scheme://host:port/path?query#fragment
	ThinkPHP.U = function(url, vars, suffix){
		var info = this.parse_url(url), path = [], param = {}, reg;

		/* 验证info */
		info.path || $.error("url格式错误！");
		url = info.path;

		/* 组装URL */
		if(0 === url.indexOf("/")){ //路由模式
			this.MODEL[0] == 0 && $.error("该URL模式不支持使用路由!(" + url + ")");

			/* 去掉右侧分割符 */
			if("/" == url.substr(-1)){
				url = url.substr(0, url.length -1)
			}
			url = ("/" == this.DEEP) ? url.substr(1) : url.substr(1).replace(/\//g, this.DEEP);
			url = "/" + url;
		} else { //非路由模式
			/* 解析URL */
			path = url.split("/");
			path = [path.pop(), path.pop(), path.pop()].reverse();
			path[1] || $.error("ThinkPHP.U(" + url + ")没有指定控制器");

			if(path[0]){
				param[this.VAR[0]] = this.MODEL[1] ? path[0].toLowerCase() : path[0];
			}

			param[this.VAR[1]] = this.MODEL[1] ? this.parse_name(path[1]) : path[1];
			param[this.VAR[2]] = path[2].toLowerCase();

			url = "?" + $.param(param);
		}

		/* 解析参数 */
		if(typeof vars === "string"){
			vars = this.parse_str(vars);
		} else if(!$.isPlainObject(vars)){
			vars = {};
		}

		/* 解析URL自带的参数 */
		info.query && $.extend(vars, this.parse_str(info.query));

		if(vars){
			url += "&" + $.param(vars);
		}

		if(0 != this.MODEL[0]){
			url = url.replace("?" + (path[0] ? this.VAR[0] : this.VAR[1]) + "=", "/")
				     .replace("&" + this.VAR[1] + "=", this.DEEP)
				     .replace("&" + this.VAR[2] + "=", this.DEEP)
				     .replace(/(\w+=&)|(&?\w+=$)/g, "")
				     .replace(/[&=]/g, this.DEEP);

			/* 添加伪静态后缀 */
			if(false !== suffix){
				suffix = suffix || this.MODEL[2].split("|")[0];
				if(suffix){
					url += "." + suffix;
				}
			}
		}

		url = this.APP + url;
		return url;
	}

	/* 设置表单的值 */
	ThinkPHP.setValue = function(name, value){
		var first = name.substr(0,1), input, i = 0, val;
		if(value === "") return;
		if("#" === first || "." === first){
			input = $(name);
		} else {
			input = $("[name='" + name + "']");
		}

		if(input.eq(0).is(":radio")) { //单选按钮
			input.filter("[value='" + value + "']").each(function(){this.checked = true});
		} else if(input.eq(0).is(":checkbox")) { //复选框
			if(!$.isArray(value)){
				val = new Array();
				val[0] = value;
			} else {
				val = value;
			}
			for(i = 0, len = val.length; i < len; i++){
				input.filter("[value='" + val[i] + "']").each(function(){this.checked = true});
			}
		} else {  //其他表单选项直接设置值
			input.val(value);
		}
	}
        
//  angular
 angular.module('Myapp', ['services.data']);

 angular.module('Myapp').controller('maincontroller',function($scope,dataService,$interval){
     $scope.yujin=false;
     $scope.tipshow=function(){
         dataService.data_get('/api/user/yujin').success(function(e){
             if(e.success){
//                 message.wav
                 $scope.yujin=true;
                 $scope.num=e.count;
                 $scope.mp3path = "<audio class='hide' loop='100' autoplay='autoplay'><source src='public/js/ext/toastr/tip.mp3' type='audio/mpeg' /></audio>";
//                 alert("发现报警!");
             }
         })
     }
     var href=0;
     $scope.ordershow=function(){
         dataService.data_get('/api/user/order_msg').success(function(e){
             if(e.success){   
//                 message.wav
                 $scope.order=true;
                 $scope.num=e.count;
                 $scope.mp3path = "<audio class='hide' loop='100' autoplay='autoplay'><source src='public/js/ext/toastr/message.wav' type='audio/mpeg' /></audio>";
             }
         })
     }

     $scope.order_c=function(){
     	$scope.order=false;
     	dataService.data_get('/api/user/order_msg_c').success(function(e){
             if(e.success){   
             	 var url="";
                 $scope.mp3path = "";
                 switch(e.data.msg_types)
					{
					case 'kh':
					  url="/admin/user/openaccount";
					  break;
					case 'dh':
					  url="/admin/user/order";
					  break;
					case 'xf':
					  url="/admin/user/reneworder";
					  break;
					case 'wx':
					  url="/admin/user/repairorder";
					  break;  
					}
					// console.log(url,e.data);
                 window.location.href=url; 
             }
         })
     }


     $scope.msg_c=function(){
     	$scope.yujin=false;
     	dataService.data_get('/api/user/yujin_c').success(function(e){
             if(e.success){   
                 $scope.mp3path = "";
                 window.location.href="/admin/user/alarm"; 
             }
         })
     	
     }

     $interval(function(){
         $scope.tipshow();
         $scope.ordershow();
     },5000)
//     console.log($scope,dataService)
 })
 angular.module('Myapp').filter('defaults', ['$sce', function ($sce) {
        return function (val, val1, isback) {
            var args = Array.prototype.slice.call(arguments);
            val = val ? val : '';
            if (!isback) {
                return $sce.trustAsHtml(val + val1);
            } else {
                return $sce.trustAsHtml(val1 + val);
            }

        };
    }]);
 angular.module("services.data", []), angular.module("services.data").factory("dataService", ["$http",
        function (a) {
            var b = {};
            b.data_post = function (url, b) {
                return a({
                    withCredentials: !0,
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                    },
                    url: url,
                    data: b
                })
            }
            //获取用户 数据默认是自己
            b.data_get = function (url, data) {
                var b = url,
                    c = new Date;
                return b += "?time=" + c.getTime(), a({
                    withCredentials: !0,
                    method: "GET",
                    url: b,
                    params: data
                })
            }


            return b;
        }
    ])

})(window, angular,jQuery);
