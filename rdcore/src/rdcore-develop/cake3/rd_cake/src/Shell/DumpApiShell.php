<?php

namespace App\Shell;

use Cake\Console\Shell;

use Cake\Datasource\ConnectionManager;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\Error;
use PhpParser\NodeDumper;

use PhpParser\{Node, NodeTraverser, NodeVisitorAbstract};

class DumpApiShell extends Shell{
	protected $controller_dir;
	protected $component_dir;
	protected $model_table_dir;

	public function initialize(){
		parent::initialize();

		$this->controller_dir	= realpath(dirname(__FILE__) . '/../Controller');
		$this->component_dir	= $this->controller_dir . '/Component';
		$this->model_table_dir	= realpath(dirname(__FILE__) . '/../Model/Table');
	}

	private function parseCode($code) {
		$lexer = new Lexer\Emulative([
		    'usedAttributes' => [
		        'comments',
		        'startLine', 'endLine',
		        'startTokenPos', 'endTokenPos',
		    ],
		    'phpVersion' => Lexer\Emulative::PHP_7_4,
		]);
		$parser = new Parser\Php7($lexer);
		return $parser->parse($code);
	}

	private function getClassName($stmts) {
		// Filter definition.
		global $class_name_;
		$class_name_ = NULL;

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
		    function enterNode(Node $node) {
		        if ($node instanceof Node\Stmt\Class_) {
		            global $class_name_;
        		    $class_name_ = $node->name->name;
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);

		$ret_class_name = $class_name_;
		$class_name_ = NULL;
		return $ret_class_name;
	}

	private function getMainModel($stmts) {
		// Filter definition.
		global $main_model_;
		$main_model_ = NULL;

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
		    function enterNode(Node $node) {
		        if ($node instanceof Node\Stmt\PropertyProperty) {

					if (property_exists($node, 'name') &&
						property_exists($node->name, 'name') &&
						$node->name->name == 'main_model' &&
						property_exists($node, 'default') &&
						property_exists($node->default, 'value')) {
						
		           		global $main_model_;
        		    	$main_model_ = $node->default->value;
					}
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);

		$main_model = $main_model_;
		$main_model_ = NULL;
		return $main_model;
	}

	private function getClassMethodNodes($stmts) {
		// Filter definition.
		global $methods_;
		$methods_ = [];

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
		    function enterNode(Node $node) {
		        if ($node instanceof Node\Stmt\ClassMethod) {
		            global $methods_;
        		    $methods_[] = $node;
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);

		$ret_methods = $methods_;
		$methods_ = [];
		return $ret_methods;
	}

	private function getMethodCalls($stmts, $method = NULL) {
		global $method_;
		global $method_calls_;
		$method_ = $method;
		$method_calls_ = [];

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
			function filterNodeByMethod(Node $node, $method) {
				if (is_null($method)) {
					return true;
				}

				if (is_string($method)) {
					$method = [$method];
				}

				if (!property_exists($node, 'name') ||
					!property_exists($node->name, 'name') ||
					$node->name->name != $method[count($method) - 1]) {
					return false;
				}

				if (count($method) > 1) {
					// TODO: This process can check request->key, 
					//       but not this->request->key.
					if (property_exists($node, 'var')) {
						array_pop($method);
						return $this->filterNodeByMethod($node->var, $method);
					} else {
						return false;
					}
				} else {
					return true;
				}
			}

		    function enterNode(Node $node) {
		        if ($node instanceof Node\Expr\MethodCall) {
					global $method_;
		            global $method_calls_;
					if ($this->filterNodeByMethod($node, $method_)) {
       		    		$method_calls_[] = $node;
					}
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);

		$ret_method_calls = $method_calls_;
		$method_ = NULL;
		$method_calls_ = [];
		return $ret_method_calls;
	}

	private function getUnsetParams($stmts, $var = NULL) {
		global $var_;
		global $params_;
		$var_ = $var;
		$params_ = [];

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
			function filterNodeByVar(Node $node, $var) {
				if (is_null($var)) {
					return true;
				}

				//print "var: ";
				//var_dump($var);
				//var_dump($node);

				if (is_string($var)) {
					$var = [$var];
				}

				if (!property_exists($node, 'vars') ||
					count($node->vars) == 0 ||
                    !property_exists($node->vars[0], 'var') ||
                    !property_exists($node->vars[0]->var, 'name') ||
                    $node->vars[0]->var->name != end($var)) {
                    return false;
                }

				if (count($var) > 1) {
					// TODO: This process can check request->key, 
					//       but not this->request->key.
					if (property_exists($node->vars[0]->var, 'var') &&
						property_exists($node->vars[0]->var->var, 'name')) {
						array_pop($var);
						if ($node->vars[0]->var->var->name == end($var)) {
							return true;
						} else {
							return false;
						}
					} else {
						return false;
					}
				} else {
					return true;
				}
				return true;
			}

			function getVarParam(Node $node, $var) {
				if (!$this->filterNodeByVar($node, $var)) {
					return NULL;
				}

				if (!property_exists($node, 'vars') ||
					!is_array($node->vars) ||
					count($node->vars) == 0 ||
					!property_exists($node->vars[0], 'dim') ||
					!property_exists($node->vars[0]->dim, 'value')) {
					return NULL;
				}

				return $node->vars[0]->dim->value;
			}

		    function enterNode(Node $node) {
		        if ($node instanceof Node\Stmt\Unset_) {
					global $var_;
					global $params_;

					$param = $this->getVarParam($node, $var_);
					if (!is_null($param)) {
						if (!array_key_exists($param, $params_)) {
							$params_[$param] = [];
						}
						$start_line = $node->getAttributes()['startLine'];
						$params_[$param][] = $start_line;
					}

		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);

		$ret_params = $params_;
		$var_ = NULL;
		$params_ = [];
		return $ret_params;
	}

	private function getIfs($stmts) {
		global $ifs_;
		$ifs_ = [];

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
		    function enterNode(Node $node) {
		        if ($node instanceof Node\Stmt\If_) {
		            global $ifs_;
					$ifs_[] = $node;
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);
		//var_dump($ifs_);

		$ret_ifs = $ifs_;
		$ifs_ = [];
		return $ret_ifs;
	}

	private function getThrows($stmts) {
		global $throws_;
		$throws_ = [];

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
		    function enterNode(Node $node) {
		        if ($node instanceof Node\Stmt\Throw_) {
		            global $throws_;
					$throws_[] = $node;
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);
		//var_dump($ifs_);

		$ret_throws = $throws_;
		$throws_ = [];
		return $ret_throws;
	}

	private function getArrayDimFetches($stmts, $var = NULL){
		global $var_;
		global $property_fetches_;
		$var_ = $var;
		$property_fetches_ = [];

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
			function filterNodeByVar(Node $node, $var) {
				if (is_null($var)) {
					return true;
				}

				//var_dump($node);

				if (is_string($var)) {
					$var = [$var];
				}

				if (!property_exists($node, 'var') ||
					!property_exists($node->var, 'name') ||
					$node->var->name != end($var)) {
					return false;
				}

				if (count($var) > 1) {
					// TODO: This process can check request->key, 
					//       but not this->request->key.
					if (property_exists($node, 'var')) {
						array_pop($var);
						return $this->filterNodeByVar($node->var, $var);
					} else {
						return false;
					}
				} else {
					return true;
				}
			}

		    function enterNode(Node $node) {
		        if ($node instanceof Node\Expr\ArrayDimFetch) {
					global $var_;
		            global $property_fetches_;

					if ($this->filterNodeByVar($node, $var_)) {
  		    			$property_fetches_[] = $node;
					}
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);

		$ret_property_fetches = $property_fetches_;
		$var_ = NULL;
		$property_fetches_ = [];
		return $ret_property_fetches;
	}

	private function getAssigns($stmts){
		global $assigns_;
		$assigns_ = [];

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract {
		    function enterNode(Node $node) {
		        if ($node instanceof Node\Expr\Assign) {
					global $assigns_;
					$assigns_[] = $node;
		            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        		}
		    }
		});

		// Traverse using our filter.
		$traverser->traverse($stmts);

		$ret_assigns = $assigns_;
		$assigns_ = [];
		return $ret_assigns;
	}

	private function getAllRequestParamsInArrayDim($stmts, $var = NULL) {
		$params = [];

		$array_dim_fetches = $this->getArrayDimFetches($stmts, $var);
		foreach ($array_dim_fetches as $array_dim_fetch) {
			if (property_exists($array_dim_fetch, 'dim') &&
			    property_exists($array_dim_fetch->dim, 'value')) {
				$param_name = $array_dim_fetch->dim->value;
				//if (!in_array($param_name, $params)) {
				//	$params[] = $param_name;
				//}
				if (!array_key_exists($param_name, $params)) {
					$params[$param_name] = [];
				}
				$start_line = $array_dim_fetch->getAttributes()['startLine'];
				if (!in_array($start_line, $params[$param_name])) {
					$params[$param_name][] = $start_line;
				}
			} else {
				//var_dump($array_dim_fetch);
			}
		}

		return $params;
	}

	private function getParamsLineInArrayDimFetches($stmts, $var = NULL) {
		$params = [];

		foreach ($stmts as $array_dim_fetch) {
			if (property_exists($array_dim_fetch, 'dim') &&
				!is_null($array_dim_fetch->dim) &&
			    property_exists($array_dim_fetch->dim, 'value')) {
				$param_name = $array_dim_fetch->dim->value;
				//if (!in_array($param_name, $params)) {
				//	$params[] = $param_name;
				//}
				if (!array_key_exists($param_name, $params)) {
					$params[$param_name] = [];
				}
				$params[$param_name][] = $array_dim_fetch->getAttributes()['startLine'];
			} else {
				//var_dump($array_dim_fetch);
			}
		}

		return $params;
	}

	private function getExcludedRequestParamsInArrayDim($stmts, $var = NULL) {
		$params = [];

		$assigns = $this->getAssigns($stmts);
		foreach ($assigns as $assign) {
			$var_params = [];
			$expr_params = [];

			$var_array_dim_fetches = $this->getArrayDimFetches(
				[$assign->var], $var);
			if (count($var_array_dim_fetches) == 0) {
				continue;
			} else {
				$var_params = $this->getParamsLineInArrayDimFetches(
								$var_array_dim_fetches, $var);
			}

			$expr_array_dim_fetches = $this->getArrayDimFetches(
				[$assign->expr], $var);
			if (count($expr_array_dim_fetches) > 0) {
				$expr_params = $this->getParamsLineInArrayDimFetches(
								$expr_array_dim_fetches, $var);
			}

			foreach ($var_params as $key => $var_lines) {
				if (array_key_exists($key, $expr_params)) {
					$expr_lines = $expr_params[$key];
					if ($var_lines[0] >= $expr_lines[0]) {
						continue;
					}
				}

				if (!array_key_exists($key, $params)) {
					$params[$key] = [];
				}
				$params[$key] = array_merge($params[$key], $var_lines);
				asort($params[$key]);
			}
		}

		return $params;
	}

	private function getRequestParamsInArrayDim($stmts, $var = NULL) {
		$base_params = $this->getAllRequestParamsInArrayDim($stmts, $var);
		//var_dump($base_params);

		$base_params = $this->filterExcludedRequestParamsInArrayDim(
						$base_params, $stmts, $var);
		//var_dump($base_params);
		return array_keys($base_params);
	}

	private function getAllRequestParamsInMethodCall($stmts, $method, &$is_empty_args = NULL){
		$params = [];

		$method_calls = $this->getMethodCalls($stmts, $method);
		foreach ($method_calls as $method_call) {
			if (!property_exists($method_call, 'args') ||
				!is_array($method_call->args)) {
				continue;
			}

			if (count($method_call->args) > 0) {
				foreach ($method_call->args as $arg) {
					if (property_exists($arg, 'value') &&
						property_exists($arg->value, 'value')) {
						$param_name = $arg->value->value;
						//if (!in_array($param_name, $params)) {
						//	$params[] = $param_name;
						//}
						if (!array_key_exists($param_name, $params)) {
							$params[$param_name] = [];
						}
						$start_line = $method_call->getAttributes()['startLine'];
						if (!in_array($start_line, $params[$param_name])) {
							$params[$param_name][] = $start_line;
						}
					}
				}
			} else {
				if (!is_null($is_empty_args)) {
					$is_empty_args = true;
				}
			}
		}
		return $params;
	}

	private function getRequestParamsInMethodCall(
						$stmts, $method, &$is_empty_args = NULL){

		$base_params = $this->getAllRequestParamsInMethodCall(
							$stmts, $method, $is_empty_args);
		//var_dump($base_params);

		$var = $this->convertMethodToFilterVar($method);
		$base_params = $this->filterExcludedRequestParamsInArrayDim(
						$base_params, $stmts, $var);
		//var_dump($base_params);

		return array_keys($base_params);
	}

	private function getAssignByMethodCall($stmts, $method) {
		$params = [];
		$var_names = [];

		$assigns = $this->getAssigns($stmts);
		//var_dump($assigns);
		foreach ($assigns as $assign) {
			$var_name = NULL;
			if (property_exists($assign, 'var') &&
				property_exists($assign->var, 'name')) {
				$var_name = $assign->var->name;
			} else {
				continue;
			}

			if (!property_exists($assign, 'expr')) {
				continue;
			}

			$is_empty_args = false;
			$params = $this->mergeLineParams($params,
						$this->getAllRequestParamsInMethodCall([$assign->expr],
						$method, $is_empty_args));
			if ($is_empty_args) {
				if (!array_key_exists($var_name, $var_names)) {
					$var_names[$var_name] = [];
				}
				$start_line = $assign->getAttributes()['startLine'];
				if (!in_array($start_line, $var_names[$var_name])) {
					$var_names[$var_name][] = $start_line;
				}
			}
		}

		//$var_names = array_unique($var_names);
		//$var_names = array_values($var_names);

		return [
			'params'	=> $params,
			'var_names'	=> $var_names,
		];
	}

	private function isRequestDataVar($stmts, $var){
		if ($var instanceof Node\Expr\PropertyFetch ||
			$var instanceof Node\Expr\MethodCall) {
			if (property_exists($var, 'name') &&
				property_exists($var->name, 'name') &&
				($var->name->name == 'data' ||
				 $var->name->name == 'getdata' ||	// typo?
				 $var->name->name == 'getData') &&
				property_exists($var, 'var') &&
				property_exists($var->var, 'name') &&
				property_exists($var->var->name, 'name') &&
				$var->var->name->name == 'request' &&
				property_exists($var->var, 'var') &&
				property_exists($var->var->var, 'name') &&
				$var->var->var->name == 'this') {
				return true;
			}
		} else if ($var instanceof Node\Expr\Variable) {
			if (property_exists($var, 'name')) {
				$var_name = $var->name;

				// NOTE: for $this->request->data();
				foreach (['getData', 'getdata', 'data'] as $key) {
					$assign = $this->getAssignByMethodCall($stmts, ['request', $key]);
					if (array_key_exists($var_name, $assign['var_names'])) {
						return true;
					}
				}

				// NOTE: for $this->request->data;
				if (array_key_exists($var_name,
					 $this->getAssignByIdentifier($stmts, ['request', 'data']))) {
					return true;
				}
			}
		} else {
			//var_dump($var);
		}

		return false;
	}

	private function isRequestQueryVar($stmts, $var){
		if ($var instanceof Node\Expr\PropertyFetch ||
			$var instanceof Node\Expr\MethodCall) {
			if (property_exists($var, 'name') &&
				property_exists($var->name, 'name') &&
				($var->name->name == 'query' ||
				 $var->name->name == 'getQuery') &&
				property_exists($var, 'var') &&
				property_exists($var->var, 'name') &&
				property_exists($var->var->name, 'name') &&
				$var->var->name->name == 'request' &&
				property_exists($var->var, 'var') &&
				property_exists($var->var->var, 'name') &&
				$var->var->var->name == 'this') {
				return true;
			}
		} else if ($var instanceof Node\Expr\Variable) {
			if (property_exists($var, 'name')) {
				$var_name = $var->name;

				// NOTE: for $this->request->query();
				foreach (['getQuery', 'query'] as $key) {
					$assign = $this->getAssignByMethodCall($stmts, ['request', $key]);
					if (array_key_exists($var_name, $assign['var_names'])) {
						return true;
					}
				}

				// NOTE: for $this->request->query;
				if (array_key_exists($var_name,
					 $this->getAssignByIdentifier($stmts, ['request', 'query']))) {
					return true;
				}
			}
		} else {
			//var_dump($var);
		}

		return false;
	}

	private function convertMethodToFilterVar($method) {
		$var = $method;
		if ($var != NULL) {
			if (preg_match('/data/i', end($var))) {
				$var[count($var) - 1] = 'data';
			} else if (preg_match('/query/i', end($var))) {
				$var[count($var) - 1] = 'query';
			}
		}
		return $var;
	}

	private function filterExcludedRequestParamsInArrayDim(
						$base_params, $stmts, $var, $ex_var = NULL) {
		$params = $base_params;

		$ex_params_list = [];
		if (!is_null($var)) {
			$ex_params_list[] = $this->getExcludedRequestParamsInArrayDim($stmts, $var);
			$ex_params_list[] = $this->getUnsetParams($stmts, $var);
		}
		if (!is_null($ex_var)) {
			$ex_params_list[] = $this->getExcludedRequestParamsInArrayDim($stmts, $ex_var);
			$ex_params_list[] = $this->getUnsetParams($stmts, $ex_var);
		}

		foreach (array_keys($params) as $key) {
			foreach ($ex_params_list as $ex_params) {
				if (array_key_exists($key, $ex_params)) {
					for ($i = count($params[$key]) - 1; $i >= 0; $i--) {
						if ($params[$key][$i] >= $ex_params[$key][0]) {
							$line_num = $params[$key][$i];
							array_pop($params[$key]);
						}
					}
				}
			}
			if (count($params[$key]) == 0) {
				unset($params[$key]);
			}
		}

		return $params;
	}

	private function getRequestParamsByVarNames($stmts, $var_names, $ex_var) {
		$params = [];
		foreach ($var_names as $var_name => $lines) {
			$base_params = $this->getAllRequestParamsInArrayDim($stmts, $var_name);
			//var_dump($base_params);

			$base_params = $this->filterExcludedRequestParamsInArrayDim(
							$base_params, $stmts, $var_name, $ex_var);
			//var_dump($base_params);

			$params = array_merge($params, array_keys($base_params));
		}
		return $params;
	}

	private function getRequestParamsInAssignByMethodCall($stmts, $method){
		$assign = $this->getAssignByMethodCall($stmts, $method);
		// Parameters used in arguments are not used here.
		// Obtains the name of the variable to be assigned.
		$var_names = $assign['var_names'];

		$ex_var = $this->convertMethodToFilterVar($method);
		return $this->getRequestParamsByVarNames($stmts, $var_names, $ex_var);
	}

	private function filterNodeByIdentifier(Node $node, $identifier){
		if (is_null($identifier)) {
			return true;
		}

		if (is_string($identifier)) {
			$identifier = [$identifier];
		}

		if (!property_exists($node, 'name') ||
			!property_exists($node->name, 'name') ||
			$node->name->name != $identifier[count($identifier) - 1]) {
			return false;
		}

		if (count($identifier) > 1) {
			// TODO: This process can check request->key, 
			//       but not this->request->key.
			if (property_exists($node, 'var')) {
				array_pop($identifier);
				return $this->filterNodeByIdentifier($node->var, $identifier);
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	private function getAssignByIdentifier($stmts, $identifier) {
		$var_names = [];

		$assigns = $this->getAssigns($stmts);
		//var_dump($assigns);
		foreach ($assigns as $assign) {
			$var_name = NULL;
			if (property_exists($assign, 'var') &&
				property_exists($assign->var, 'name')) {
				$var_name = $assign->var->name;
			} else {
				continue;
			}

			if (property_exists($assign, 'expr') &&
				$this->filterNodeByIdentifier($assign->expr, $identifier)) {
				//$var_names[] = $var_name;
				if (!array_key_exists($var_name, $var_names)) {
					$var_names[$var_name] = [];
				}
				$start_line = $assign->getAttributes()['startLine'];
				if (!in_array($start_line, $var_names[$var_name])) {
					$var_names[$var_name][] = $start_line;
				}
			}
		}
		//var_dump($var_names);

		//$var_names = array_unique($var_names);
		//$var_names = array_values($var_names);

		return $var_names;
	}

	private function getRequestParamsInAssign($stmts, $identifier){
		$var_names = $this->getAssignByIdentifier($stmts, $identifier);
		//var_dump($var_names);

		$ex_var = $this->convertMethodToFilterVar($identifier);
		return $this->getRequestParamsByVarNames($stmts, $var_names, $ex_var);
	}

	private function getComponentNames($stmts) {
		$component_names = [];
		$methods = $this->getClassMethodNodes($stmts);
		foreach ($methods as $method) {
			if ($method->name->name != "initialize") {
				continue;
			}
			$method_calls = $this->getMethodCalls($method->stmts);
			foreach ($method_calls as $method_call) {
				if ($method_call->name->name != "loadComponent") {
					continue;
				}
				$component_names[] = $method_call->args[0]->value->value;
			}
		}
		return $component_names;
	}

	private function procComponents(&$components, &$main_models){
		$components = [];
		$main_models = [];

		$component_files = glob($this->component_dir . '/*.php');
		foreach ($component_files as $component_file) {
			$method_list = [];

			try {
				$code = file_get_contents($component_file);
				$stmts = $this->parseCode($code);
			} catch (Exception $e) {
				echo $e->getMessage();
				echo "\n";
				continue;
			}

			$class_name = $this->getClassName($stmts);
			$component_name = preg_replace("/Component$/", '', $class_name);

			$methods = $this->getClassMethodNodes($stmts);
			foreach ($methods as $method) {
				$method_name = $method->name->name;
				$method_list[$method_name] = $method;
			}
			$components[$component_name] = $method_list;

			$main_model = $this->getMainModel($stmts);
			if (!is_null($main_model)) {
				$main_models[$component_name] = $main_model;
			}
		}
	}

	private function procModelTables() {
		$model_tables = [];

		$model_table_files = glob($this->model_table_dir . '/*.php');
		foreach ($model_table_files as $model_table_file) {
			$method_list = [];

			try {
				$code = file_get_contents($model_table_file);
				$stmts = $this->parseCode($code);
			} catch (Exception $e) {
				echo $e->getMessage();
				echo "\n";
				continue;
			}

			$class_name = $this->getClassName($stmts);
			$model_table_name = preg_replace("/Table$/", '', $class_name);

			$methods = $this->getClassMethodNodes($stmts);
			foreach ($methods as $method) {
				$method_name = $method->name->name;
				$method_list[$method_name] = $method;
			}
			$model_tables[$model_table_name] = $method_list;
		}

		return $model_tables;
	}

	private function ignoreErrorEntity($class_name, $method_name) {
		switch ($class_name) {
		case 'AccessProvidersController':
			switch ($method_name) {
			case 'test':
			case 'changeParent':
			case 'changePassword':
				return true;
			}
			break;
		case 'ApHelper':
			switch ($method_name) {
			case '_update_fetched_info':
			case '_update_wbw_channel':
				return true;
			}
			break;
		case 'ApProfilesController':
			switch ($method_name) {
			case '_add_dynamic':
			case '_add_dynamic_pair':
			case '_change_dynamic_shortname':
			case 'apProfileExitAdd':
			case 'apProfileExitEdit':
				return true;
			}
			break;
		case 'ApReportsController':
			switch ($method_name) {
			case '_do_ap_load':
			case '_do_radio_interfaces':
			case '_do_uptm_history':
			case '_new_ap_system':
			case '_new_report':
				return true;
			}
			break;
		case 'ApsController':
			switch ($method_name) {
			case 'getConfigForAp':
				return true;
			}
			break;
		case 'CloudsController':
			switch ($method_name) {
			case 'mapNodeDelete':
			case 'mapNodeSave':
			case 'mapOverviewDelete':
			case 'mapOverviewSave':
				return true;
			}
			break;
		case 'CoaRequestsController':
			switch ($method_name) {
			case '_add_with_missing_info':	// Called when trouble occurs.
			case 'coaForMac':
			case 'coaReply':
			case '_informNode':
			case '_process_coa':	// TODO: Requested data format is unknown.
				return true;
			}
			break;
		case 'CountriesController':
			switch ($method_name) {
			case 'add':
				return true;
			}
			break;
		case 'DashboardController':
			switch ($method_name) {
			case 'settingsSubmit':
				return true;
			}
			break;
		case 'DynamicClientsController':
			switch ($method_name) {
			case '_add_dynamic_client_realm':
			case 'uploadPhoto':
				return true;
			}
			break;
		case 'DynamicDetailsController':
			switch ($method_name) {
			case 'thankYou':
				return true;
			}
			break;
		case 'DynamicDetailTranslationsController':
			switch ($method_name) {
			case '_add_or_edit_piar':
			case 'saveTweaks':
				return true;
			}
			break;
		case 'FreeRadiusController':
			switch ($method_name) {
			case 'startDebug':
			case 'timeDebug':
				return true;
			}
			break;
		case 'HardwareOwnersController':
			switch ($method_name) {
			case 'delete':
				return true;
			}
			break;
		case 'HomeServerPoolsController':
			switch ($method_name) {
			case 'add':
				return true;
			}
			break;
		case 'IpPoolsController':
			switch ($method_name) {
			case 'addIp':
			case 'edit':	// $cdata = $this->request->getData(); is missing?
				return true;
			}
			break;
		case 'Kicker':
			switch ($method_name) {
			case 'kick':
				return true;
			}
			break;
		case 'MeshesController':
			switch ($method_name) {
			case '_add_dynamic':
			case '_add_dynamic_pair':
			case 'mapNodeDelete':
			case 'mapNodeSave':
			case 'meshEntryAdd':
			case 'meshExitAdd':
			case 'meshExitEdit':
				return true;
			}
			break;
		case 'MeshHelper':
			switch ($method_name) {
			case '_update_fetched_info':
				return true;
			}
			break;
		case 'MeshReportsController':
			switch ($method_name) {
			case '_do_node_load':
			case '_do_radio_interfaces':
			case '_new_node_system':
			case '_new_report':
				return true;
			}
			break;
		case 'NasController':
			switch ($method_name) {
			case '_add_nas_realm':
			case '_add_nas_tag':
			case 'addOpenVpn':
			case 'addPptp':
			case 'editMapPref':
				return true;
			}
			break;
		case 'NodeReportsController':
			switch ($method_name) {
			case '_addWbwInfo':
			case '_do_node_load':
			case '_do_node_system_info':
			case '_do_radio_interfaces':
			case '_do_uptm_history':
			case '_do_vis':
			case '_new_do_uptm_history':
			case '_node_stations':	// Note that it has a grandchild key.
			case 'startScanForNode':
			case 'submitRogueReport':
			case '_update_last_contact':
				return true;
			}
			break;
		case 'NodesController':
			switch ($method_name) {
			case '_completeCheckout':
			case 'GetConfigForNode':
				return true;
			}
			break;
		case 'PhraseValuesController':
			switch ($method_name) {
			case 'addKey':
			case 'addLanguage':
			case 'updatePhrase':
				return true;
			}
			break;
		case 'ProfilesController':
			switch ($method_name) {
			case '_doRadius':
			case 'manageComponents':
				return true;
			}
			break;
		case 'RegistrationRequestsController':
			switch ($method_name) {
			case 'giveCode':
			case 'sendCode':
				return true;
			}
			break;
		case 'SettingsController':
			switch ($method_name) {
			case 'editLicense':
				return true;
			}
			break;
		case 'SoftflowsController':
			switch ($method_name) {
			case 'report':
				return true;
			}
			break;
		case 'ThirdPartyAuthsController':
			switch ($method_name) {
			case '_authForRadius':
			case '_record_or_update_info':
				return true;
			}
			break;
		case 'TreeTagsController':
			switch ($method_name) {
			case 'mapSave':
				return true;
			}
			break;
		case 'UnknownNodesController':
			switch ($method_name) {
			case 'claimOwnership':
			case 'releaseOwnership':
				return true;
			}
			break;
		case 'WizardsController':
			switch ($method_name) {
			case '_add_items':
			case '_complete_ap_profile':
			case '_complete_mesh':
				return true;
			}
			break;
		case 'XwfApiController':
			switch ($method_name) {
			case 'adhocCommand':
				return true;
			}
			break;
		}

		return false;
	}

	private function manualListEntity($class_name, $method_name,
									  &$is_data, &$is_query) {
		switch ($class_name) {
		case 'ApProfilesController':
			switch ($method_name) {
			case 'apProfileApAdd':
			case 'apProfileApEdit':
				$is_data = true;
				$params = [
							'wbw_ssid', 'wbw_encryption', 'wbw_key', 'wbw_device',
							'wbw_radio_0', 'wbw_radio_1', 'wbw_radio_2','wbw_wan_bridge',
							'wan_static_ipaddr', 'wan_static_netmask',
							'wan_static_gateway', 'wan_static_dns_1', 'wan_static_dns_2',
							'wan_pppoe_username', 'wan_pppoe_password', 'wan_pppoe_dns_1',
							'wan_pppoe_dns_2', 'wan_pppoe_mac', 'wan_pppoe_mtu',
							'wifi_static_ssid', 'wifi_static_encryption', 'wifi_static_key',
							'wifi_static_device', 'wifi_static_radio_0', 'wifi_static_radio_1',
							'wifi_static_radio_2', 'wifi_static_ipaddr', 'wifi_static_netmask',
							'wifi_static_gateway', 'wifi_static_dns_1', 'wifi_static_dns_2',
							'wifi_static_wan_bridge',
							'wifi_pppoe_ssid', 'wifi_pppoe_encryption', 'wifi_pppoe_key',
							'wifi_pppoe_device', 'wifi_pppoe_radio_0', 'wifi_pppoe_radio_1',
							'wifi_pppoe_radio_2', 'wifi_pppoe_username', 'wifi_pppoe_password',
							'wifi_pppoe_mac', 'wifi_pppoe_mtu', 'wifi_pppoe_wan_bridge',
							'qmi_auth', 'qmi_username', 'qmi_password', 'qmi_apn',
							'qmi_pincode', 'qmi_wan_bridge',
						  ];
				// In ApProfilesController::apProfileApAdd(), "^radio\d+_(disabled|..."
				// but in mHardware.js, it is "radio_0_disabled",
				// so I doubt if it works correctly.
				for ($i = 0; $i < 3; $i ++) {
					foreach (['disabled','band','mode','width','txpower','include_distance',
							  'distance','include_beacon_int','beacon_int','ht_capab','mesh',
							  'ap','config','channel_five','channel_two','noscan'] as $key) {
						$params[] = sprintf("radio%d_%s", $i, $key);
					}
				}

				if ($method_name == 'apProfileApAdd') {
					$params = array_merge($params, ['wbw_freq']);
				}

				return $params;
			}
			break;
		case 'CoaRequestsController':
			switch ($method_name) {
			case 'edit':
				$is_data = true;
				return	[
							'cp_radius_1', 'cp_radius_2', 'cp_radius_secret', 'cp_uam_url',
							'cp_uam_secret', 'cp_swap_octet', 'cp_swap_octet', 'cp_mac_auth',
							'cp_coova_optional',
						];
			}
			break;
		case 'DevicesController':
			switch ($method_name) {
			case 'enableDisable':
				$is_data = true;
				return	['(numerical value)'];
			}
			break;
		case 'DynamicDetailsController':
			switch ($method_name) {
			case 'editSocialLogin':
				$is_data = true;
				$params = [];
				foreach (['fb','gp','tw'] as $prefix) {
					foreach (['enable', 'voucher_or_user', 'secret',
							  'id', 'realm', 'profile', 'record_info'] as $key) {
						$params[] = sprintf("%s_%s", $prefix, $key);
					}
				}
				return $params;
			}
			break;
		case 'HardwaresController':
			switch ($method_name) {
			case '_processRadios':
				$is_data = true;
				$params = [];
				for ($i = 0; $i < 3; $i ++) {
					foreach (['txpower', 'include_beacon_int', 'beacon_int',
							  'include_distance', 'distance', 'ht_capab', 'mesh', 'ap',
							  'config', 'disabled', 'band', 'mode', 'width'] as $key) {
						$params[] = sprintf("radio_%d_%s", $i, $key);
					}
				}
				return $params;
			}
			break;
		case 'HomeServerPoolsController':
			switch ($method_name) {
			case '_processHomeServers':
				$is_data = true;
				$params = [];
				for ($i = 1; $i <= 3; $i ++) {
					foreach (['id', 'accept_coa', 'type', 'ipaddr', 'port', 'secret',
							  'response_window', 'zombie_period', 'revive_interval',] as $key) {
						$params[] = sprintf("hs_%d_%s", $i, $key);
					}
				}
				return $params;
			}
			break;
		case 'MeshesController':
			switch ($method_name) {
			case 'mapPrefEdit':
				$is_data = true;
				return	['map_zoom','map_type','map_lat','map_lng'];
			case 'meshNodeAdd':
			case 'meshNodeEdit':
				$is_data = true;
				$params = [
							'wbw_ssid', 'wbw_encryption', 'wbw_key', 'wbw_device',
							'wbw_radio_0', 'wbw_radio_1', 'wbw_radio_2','wbw_wan_bridge',
							'wan_static_ipaddr', 'wan_static_netmask',
							'wan_static_gateway', 'wan_static_dns_1', 'wan_static_dns_2',
							'wan_pppoe_username', 'wan_pppoe_password', 'wan_pppoe_dns_1',
							'wan_pppoe_dns_2', 'wan_pppoe_mac', 'wan_pppoe_mtu',
							'wifi_static_ssid', 'wifi_static_encryption', 'wifi_static_key',
							'wifi_static_device', 'wifi_static_radio_0', 'wifi_static_radio_1',
							'wifi_static_radio_2', 'wifi_static_ipaddr', 'wifi_static_netmask',
							'wifi_static_gateway', 'wifi_static_dns_1', 'wifi_static_dns_2',
							'wifi_static_wan_bridge',
							'wifi_pppoe_ssid', 'wifi_pppoe_encryption', 'wifi_pppoe_key',
							'wifi_pppoe_device', 'wifi_pppoe_radio_0', 'wifi_pppoe_radio_1',
							'wifi_pppoe_radio_2', 'wifi_pppoe_username', 'wifi_pppoe_password',
							'wifi_pppoe_mac', 'wifi_pppoe_mtu', 'wifi_pppoe_wan_bridge',
							'qmi_auth', 'qmi_username', 'qmi_password', 'qmi_apn',
							'qmi_pincode', 'qmi_wan_bridge',
						  ];
				// In ApProfilesController::apProfileApAdd(), "^radio\d+_(disabled|..."
				// but in mHardware.js, it is "radio_0_disabled",
				// so I doubt if it works correctly.
				for ($i = 0; $i < 3; $i ++) {
					foreach (['disabled','band','mode','width','txpower','include_distance',
							  'distance','include_beacon_int','beacon_int','ht_capab','mesh',
							  'ap','config','channel_five','channel_two','noscan'] as $key) {
						$params[] = sprintf("radio%d_%s", $i, $key);
					}
				}

				if ($method_name == 'meshNodeAdd') {
					$params = array_merge($params, ['wbw_freq']);
				}

				return $params;

			}
			break;
		case 'PermanentUserNotificationsController':
			switch ($method_name) {
			case 'edit':
				$is_data = true;
				return	['active'];
			}
			break;
		case 'PermanentUsersController':
			switch ($method_name) {
			case 'enableDisable':
				$is_data = true;
				return	['(numerical value)'];
			}
			break;
		case 'ProfilesController':
			switch ($method_name) {
			case 'simpleAdd':
			case 'simpleEdit':
				$is_data = true;
				return ['available_to_siblings', 'data_limit_mac', 'time_limit_mac',
						'speed_limit_enabled', 'time_limit_enabled', 'data_limit_enabled'];
			}
			break;
		case 'RadacctsController':
			switch ($method_name) {
			case 'closeOpen':
				$is_query = true;
				return	['(numerical value)'];
			}
			break;
		case 'SettingsController':
			switch ($method_name) {
			case 'save':
				return ['email_enabled','email_ssl'];
			}
			break;
		case 'WizardsController':
			switch ($method_name) {
			case 'newSiteStepTwo':
				return ['voucher_login_check', 'user_login_check',
						'eth_br_for_all', 'connect_check'];
			}
			break;
		}

		return [];
	}

	private function getExcludedRequestParamsLines($stmts) {
		$data_params = [];
		$query_params = [];

		// 1. $this->request->data[$key] = 1;
		$data_params = $this->mergeLineParams($data_params,
			$this->getExcludedRequestParamsInArrayDim($stmts, ['request', 'data']));
		//var_dump($data_params);

		// 2. $this->request->query[$key] = 1;
		$query_params = $this->mergeLineParams($query_params,
			$this->getExcludedRequestParamsInArrayDim($stmts, ['request', 'query']));
		//var_dump($query_params);

		// 3. $this->data[$key];
		$data_params = $this->mergeLineParams($data_params,
			$this->getExcludedRequestParamsInArrayDim($stmts, ['this', 'data']));
		//var_dump($data_params);

		// 4. unset($this->request->data[$key]);
		$data_params = $this->mergeLineParams($data_params,
			$this->getUnsetParams($stmts, ['request', 'data']));
		//var_dump($data_params);

		// 5. unset($this->request->query[$key]);
		$query_params = $this->mergeLineParams($query_params,
			$this->getUnsetParams($stmts, ['request', 'query']));
		//var_dump($data_params);

		return [
			'data'	=> $data_params,
			'query'	=> $query_params,
		];
	}

	private function getParamsByLoadComponent($method_call) {
		$params = ['data' => [], 'query' => [], 'model' => []];

		// NOTE: This method obtains field information from the following code.
		//
        // $this->loadComponent('Auth', [
        //     'authenticate' => [
        //         'Form' => [
        //             'userModel' => 'Users',
        //             'fields' => ['username' => 'username', 'password' => 'password'],
        //             'passwordHasher' => [
        //                 'className' => 'Fallback',
        //                 'hashers' => [
        //                     'Default',
        //                     'Weak' => ['hashType' => 'sha1']
        //                 ]
        //             ]
        //         ]
        //     ]
        // ]);

		if (!property_exists($method_call, 'args') ||
			!is_array($method_call->args) ||
			count($method_call->args) < 2 ||
			!property_exists($method_call->args[0], 'value') ||
			!property_exists($method_call->args[0]->value, 'value') ||
			$method_call->args[0]->value->value != 'Auth') {
			return $params;
		}

		// TODO: need to check property
		$authenticate = $method_call->args[1]->value->items[0]->value;
		$form = $authenticate->items[0]->value;

		foreach ($form->items as $item) {
			if ($item->key->value != 'fields') {
				continue;
			}

			$values = $item->value->items;
			foreach ($values as $value) {
				$item = $value->value->value;
				if (!in_array($item, $params['data'])) {
					$params['data'][] = $item;
				}
			}
		}

		return $params;
	}

	private function procMethodCalls($class_name, $method_name, $components,
									 $model_tables, $class_methods,
									 $main_model, $component_main_models, $stmts){
		$params = ['data' => [], 'query' => [], 'model' => []];

		$method_calls = $this->getMethodCalls($stmts);
		foreach ($method_calls as $method_call) {
			if (!property_exists($method_call, 'name') ||
				!property_exists($method_call->name, 'name')) {
				continue;
			}

			$child_method_name 		= $method_call->name->name;
			$child_component_name	= NULL;
			$is_this				= false;

			if ($method_name == $child_method_name) {
				continue;
			}

			//printf("call %s\n", $child_method_name);

			if ($child_method_name == 'loadComponent') {
				// $this->loadComponent($component_name, [
				//						'model' => $key]);

				$tmp_params = ['data' => [], 'query' => [], 'model' => []];
				$tmp_params['model'] = $this->getComponentModelNames([$method_call]);
				$params = $this->mergeParams($params, $tmp_params);

				$params = $this->mergeParams($params,
							$this->getParamsByLoadComponent($method_call));

				continue;
			} else if ($child_method_name == 'loadModel') {
				// $this->loadModel($key);

				$tmp_params = ['data' => [], 'query' => [], 'model' => []];
				$tmp_params['model'] = $this->getModelNames([$method_call], $main_model);
				$params = $this->mergeParams($params, $tmp_params);
				continue;
			}

			$child_class_name = NULL;
			$model_name = NULL;
			if (property_exists($method_call, 'var') &&
				property_exists($method_call->var, 'name')) {

				//var_dump($method_call);
				if (property_exists($method_call->var->name, 'name')) {
					if (is_string($method_call->var->name->name)) {
						$child_component_name = $method_call->var->name->name;
						$child_class_name = $child_component_name;
						if (array_key_exists($child_component_name, $model_tables)) {
							$model_name = $child_component_name;
						}
						//printf("  %s::%s\n", $child_component_name, $child_method_name);
					} else if (property_exists($method_call->var->name->name, 'name') &&
							   $method_call->var->name->name == 'main_model') {
						$model_name = $main_model;
						//printf("%s::%s\n", $model_name, $child_method_name);
					} else {
						//var_dump($method_call);
					}
				} else if ($method_call->var->name == 'this') {
					$is_this = true;
					$child_class_name = $class_name;
				} else if (is_object($method_call->var->name) &&
						   property_exists($method_call->var->name, 'value') &&
						   array_key_exists($method_call->var->name->value, $model_tables)) {
					$model_name = $method_call->var->name->value;
				} else {
					//var_dump($method_call);
				}
			}

			//printf("call %s::%s (%s)\n", $child_class_name, $child_method_name, $model_name);

			$child_stmts = NULL;
			$child_main_model = NULL;
			if (!is_null($child_component_name)) {
				if (array_key_exists($child_component_name, $components)) {
					if (array_key_exists($child_method_name,
										 $components[$child_component_name])) {
						//var_dump($components[$child_component_name][$child_method_name]);
						$child_stmts = $components[$child_component_name]
										[$child_method_name]->stmts;
					}
					if (array_key_exists($child_component_name, $component_main_models)) {
						$child_main_model = $component_main_models[$child_component_name];
					}
				} else {
					// TODO: need to handle
					//printf("%s\n", $child_component_name);
				}
			} else if ($is_this) {
				if (array_key_exists($child_method_name, $class_methods)) {
					//var_dump($class_methods[$child_method_name]->stmts);
					$child_stmts = $class_methods[$child_method_name]->stmts;
					$child_main_model = $main_model;
				} else if (array_key_exists($class_name, $components) &&
						   array_key_exists($child_method_name,
											$components[$class_name])) {
					$child_stmts = $components[$class_name][$child_method_name]->stmts;
					$child_main_model = $main_model;
				} else {
					//printf("%s\n", $child_method_name);
				}
			} else {
				//printf("%s\n", $child_component_name);
			}

			if (!is_null($child_stmts)) {
				// Gets access to the request.
				$params = $this->mergeParams($params,
							$this->getRequestParams($child_stmts));
				//printf("pre call %s::%s\n", $child_class_name, $child_method_name);
				//var_dump($params);

				// Get access to the request in the method being called.
				$params = $this->mergeParams($params,
							$this->procMethodCalls($child_class_name,
												   $child_method_name,
												   $components,
												   $model_tables,
												   $class_methods,
												   $child_main_model,
												   $component_main_models,
												   $child_stmts));
				//printf("after call %s::%s\n", $child_class_name, $child_method_name);
				//var_dump($params);
			} else if (!is_null($model_name)) {
				if (array_key_exists($model_name, $model_tables)) {
					$columns = $this->getModelColumns($model_name);
					$start_line = $method_call->getAttributes()['startLine'];
					$ex_params = $this->getExcludedRequestParamsLines($stmts);
					$is_data = false;
					$is_query = false;

					if ($child_method_name == 'newEntity') {
						if (count($method_call->args) > 0) {
							$var = $method_call->args[0]->value;

							// Remove id when creating a new one.
							$columns = array_diff($columns, ['id']);
							$columns = array_values($columns);

							if ($this->isRequestDataVar($stmts, $var)) {
								$is_data = true;
							} else if ($this->isRequestQueryVar($stmts, $var)) {
								$is_query = true;
							} else if ($this->ignoreErrorEntity($class_name, $method_name)) {
							} else {
								$columns = $this->manualListEntity(
													$class_name, $method_name,
													$is_data, $is_query);
								if (count($columns) == 0) {
									//printf("***** %s::%s call %s::%s\n",
									//	   $class_name, $method_name,
									//	   $model_name, $child_method_name);
									//var_dump($method_call);
								}
							}
						}
					} else if ($child_method_name == 'patchEntity') {
						//var_dump($method_call);
						if (count($method_call->args) > 1) {
							$var = $method_call->args[1]->value;

							if ($this->isRequestDataVar($stmts, $var)) {
								$is_data = true;
							} else if ($this->isRequestQueryVar($stmts, $var)) {
								$is_query = true;
							} else if ($this->ignoreErrorEntity($class_name, $method_name)) {
							} else {
								$columns = $this->manualListEntity(
													$class_name, $method_name,
													$is_data, $is_query);
								if (count($columns) == 0) {
									//printf("***** %s::%s call %s::%s\n",
									//	   $class_name, $method_name,
									//	   $model_name, $child_method_name);
									//var_dump($method_call);
								}
							}
						}
					} else if ($child_method_name == 'entityBasedOnPost') {
						switch ($model_name) {
						case 'Realms':
							$columns = ['realm', 'realm_id'];
							break;
						case 'Profiles':
							$columns = ['profile', 'profile_id'];
							break;
						}
						$is_data = true;
						$ex_params = ['data' => [], 'query' => []];
					} else {
						//printf("call %s::%s\n", $model_name, $child_method_name);
					}

					$key = NULL;
					if ($is_data) {
						$key = 'data';
					} else if ($is_query) {
						$key = 'query';
					}

					if (!is_null($key)) {
						foreach ($ex_params[$key] as $ex_param => $lines) {
							foreach ($lines as $line) {
								if ($start_line >= $line) {
									if (in_array($ex_param, $columns)) {
										$columns = array_diff($columns, [$ex_param]);
									}
									break;
								}
							}
						}
						$tmp_params = ['data' => [], 'query' => [], 'model' => []];
						$tmp_params[$key] = $columns;
						$params = $this->mergeParams($params, $tmp_params);
					}

					//var_dump($params);
				} else {
					// XXX: 
					printf("model %s is missing!\n", $model_name);
				}
			}
		}

		return $params;
	}

	private function getUsableHttpMethods($stmts) {
		$ifs = $this->getIfs($stmts);

		$positive_http_methods = [];
		$negative_http_methods = [];
		foreach ($ifs as $if) {
			$method_calls = $this->getMethodCalls([$if], ['request', 'is']);
			if (count($method_calls) == 0) {
				continue;
			}

			$is_negative = false;
			if (property_exists($if, 'cond') &&
				$if->cond instanceof Node\Expr\BooleanNot) {
				$is_negative = true;
			}

			$raise_exception = false;
			$throws = $this->getThrows($if->stmts);
			if (count($throws) > 0 &&
				property_exists($throws[0], 'expr') &&
				property_exists($throws[0]->expr, 'class') &&
				property_exists($throws[0]->expr->class, 'parts') &&
				is_array($throws[0]->expr->class->parts) &&
				count($throws[0]->expr->class->parts) > 0 &&
				$throws[0]->expr->class->parts[0] == "MethodNotAllowedException") {
				$raise_exception = true;
			}

			foreach ($method_calls as $method_call) {
				if (property_exists($method_call, 'args') &&
					is_array($method_call->args) &&
					count($method_call->args) > 0 &&
					is_object($method_call->args[0]) &&
					property_exists($method_call->args[0], 'value') &&
					property_exists($method_call->args[0]->value, 'value')) {
				
					$http_method = $method_call->args[0]->value->value;
					if (!in_array($http_method, ['get', 'post', 'put', 'delete', 'patch'])) {
						continue;
					}

					if ($is_negative xor $raise_exception) {
						$negative_http_methods[$http_method] = true;
					} else {
						$positive_http_methods[$http_method] = true;
					}
				}
			}
		}

		if (count($positive_http_methods) > 0) {
			$usable_http_methods = $positive_http_methods;
		} else {
			$usable_http_methods = array(
				'get' => true, 'post' => true, 'put' => true,
				'delete' => true, 'patch' => true);
		}

		foreach (array_keys($negative_http_methods) as $key) {
			unset($usable_http_methods[$key]);
		}

		$usable_http_methods = array_change_key_case(
								$usable_http_methods, CASE_UPPER);

		return array_keys($usable_http_methods);
	}

	private function getRequestParams($stmts) {
		$data_params = [];
		$query_params = [];

		// 1. $this->request->data[$key];

		$data_params = array_merge($data_params,
					$this->getRequestParamsInArrayDim(
						$stmts, ['request', 'data']));
		//var_dump($data_params);

		// 2. $this->request->query[$key];

		$query_params = array_merge($query_params,
					$this->getRequestParamsInArrayDim(
						$stmts, ['request', 'query']));
		//var_dump($query_params);

		// 3. $this->request->getData($key);

		// Get access to the request in the method.
		$data_params = array_merge($data_params,
					$this->getRequestParamsInMethodCall(
						$stmts, ['request', 'getData']));
		//var_dump($data_params);

		// 4. $this->request->getQuery($key);

		// Get access to the request in the method.
		$query_params = array_merge($query_params,
					$this->getRequestParamsInMethodCall(
						$stmts, ['request', 'getQuery']));
		//var_dump($query_params);

		// 5. $cdata = $this->request->getData();
		//	  $cdata[$key];

		$data_params = array_merge($data_params,
					$this->getRequestParamsInAssignByMethodCall(
						$stmts, ['request', 'getData']));
		//var_dump($data_params);

		// 6. $cquery = $this->request->getQuery();
		// 	  $cquery[$key];

		$query_params = array_merge($query_params,
					$this->getRequestParamsInAssignByMethodCall(
						$stmts, ['request', 'getQuery']));
		//var_dump($query_params);

		// 7. $data = $this->request->data;
		//    $data[$key];

		$data_params = array_merge($data_params,
					$this->getRequestParamsInAssign(
						$stmts, ['request', 'data']));
		//var_dump($data_params);

		// 8. $query = $this->request->data;
		//    $query[$key];

		$query_params = array_merge($query_params,
					$this->getRequestParamsInAssign(
						$stmts, ['request', 'query']));

		// 9. $this->data[$key];

		$data_params = array_merge($data_params,
					$this->getRequestParamsInArrayDim(
						$stmts, ['this', 'data']));
		//var_dump($data_params);

		// 10. $this->request->getQuery[$key];

		$query_params = array_merge($query_params,
					$this->getRequestParamsInArrayDim(
						$stmts, ['request', 'getQuery']));
		//var_dump($query_params);

		// 11. $cquery = $this->request->getQueryParams();
		// 	   $cquery[$key];

		$query_params = array_merge($query_params,
					$this->getRequestParamsInAssignByMethodCall(
						$stmts, ['request', 'getQueryParams']));

		asort($data_params);
		$data_params = array_unique($data_params);
		$data_params = array_values($data_params);

		asort($query_params);
		$query_params = array_unique($query_params);
		$query_params = array_values($query_params);

		$params = array(
			'data'	=> $data_params,
			'query'	=> $query_params,
			'model' => [],
		);

		return $params;
	}

	private function mergeParams($params0, $params1) {
		$params = [];
		foreach (['data', 'query', 'model'] as $key) {
			$exist_params0 = array_key_exists($key, $params0);
			$exist_params1 = array_key_exists($key, $params1);

			if ($exist_params0 && $exist_params1) {
				$tmp_params = array_merge($params0[$key], $params1[$key]);
				asort($tmp_params);
				$tmp_params = array_unique($tmp_params);
				$tmp_params = array_values($tmp_params);
			} else if ($exist_params0) {
				$tmp_params = $params0[$key];
			} else if ($exist_params1) {
				$tmp_params = $params1[$key];
			} else {
				continue;
			}

			$params[$key] = $tmp_params;
		}
		return $params;
	}

	private function mergeLineParams($params0, $params1) {
		$params = [];
		foreach (array_keys($params0) as $key) {
			$params[$key] = $params0[$key];
			if (array_key_exists($key, $params1)) {
				foreach ($params1[$key] as $line) {
					if (!in_array($line, $params[$key])) {
						$params[$key][] = $params1[$key];
					}
				}
			}
		}
		foreach (array_keys($params1) as $key) {
			if (!array_key_exists($key, $params)) {
				$params[$key] = $params1[$key];
			}
		}
		return $params;
	}

	private function getComponentModelNames($stmts) {
		// $this->loadComponent($component_name, [
		//						'model' => $key]);

		$models = [];

		$method_calls = $this->getMethodCalls($stmts, 'loadComponent');
		foreach ($method_calls as $method_call) {
			if (!property_exists($method_call, 'args') ||
				!is_array($method_call->args)) {
				continue;
			}

			if (count($method_call->args) > 1 &&
				property_exists($method_call->args[1], 'value') &&
				$method_call->args[1]->value instanceof Node\Expr\Array_ &&
				property_exists($method_call->args[1]->value, 'items')) {
				foreach ($method_call->args[1]->value->items as $item) {
					if (!property_exists($item, 'key') ||
						!property_exists($item->key, 'value') ||
						$item->key->value != 'model' ||
						!property_exists($item, 'value') ||
						!property_exists($item->value, 'value')) {
						continue;
					}
					$models[] = $item->value->value;
				}
			}
		}

		asort($models);
		$models = array_unique($models);
		$models = array_values($models);
		return $models;
	}

	private function getModelNames($stmts, $main_model = NULL) {
		$models = [];

		$method_calls = $this->getMethodCalls($stmts, ['loadModel']);
		foreach ($method_calls as $method_call) {
			if (!property_exists($method_call, 'args') ||
				!is_array($method_call->args)) {
				continue;
			}


			if (count($method_call->args) > 0) {
				foreach ($method_call->args as $arg) {
					if (property_exists($arg, 'value')) {
						if (property_exists($arg->value, 'value')) {
							if (!in_array($arg->value->value, $models)) {
								$models[] = $arg->value->value;
							}
						} else if (property_exists($arg->value, 'name') &&
								   property_exists($arg->value->name, 'name') &&
								   $arg->value->name->name == 'main_model' &&
								   !is_null($main_model)) {
							if (!in_array($main_model, $models)) {
								$models[] = $main_model;
							}
						}
					}
				}
			}
		}

		asort($models);
		$models = array_unique($models);
		$models = array_values($models);
		return $models;
	}

	private function checkSkipController($class_name) {
		$skip_class_names = [
			'AccountUsersController',
			'ActionsController',
			'AnalyticsController',
			'ApActionsController',
			'ApReportsController',
			'CoaRequestsController',
			'CountriesController',
			'DeviceTopdataController',
			'ErrorController',
			'FiltersController',
			'FirmwareKeysController',
			'FreeRadiusController',
			'GroupsController',
			'IpPoolsController',
			'LanguagesController',
			'LimitsController',
			'MeshNodeStateApiController',
			'MeshOverviewMapsController',
			'MeshTopdataController',
			'NaStatesController',
			'NasController',
			'NetworkOverviewsController',
			'NodeActionsController',
			'NodeReportsController',
			'NodesController',
			'NotificationListsController',
			'PagesController',
			'PermanentUserNotificationsController',
			'PhraseKeysController',
			'PhraseValuesController',
			'RadchecksController',
			'RdClientsController',
			'RegisterUsersController',
			'RegistrationRequestsController',
			'ReportingAdminController',
			'RuckusProxyController',
			'SsidsController',
			'TagsController',
			'ThirdPartyAuthsController',
			'ToolsController',
			'TrafficClassesController',
			'TreeTagsController',
			'UnknownApsController',
			'XwfApiController',

			'AttributeConvertsController',
			'HomeServerPoolsController',
			'HomeServersController',
			'ProxiesController',
			'ProxyDecisionConditionsController',
			'ProxyRealmsController',
		];

		if (in_array($class_name, $skip_class_names)) {
			return true;
		} else {
			return false;
		}
	}

	private function checkSkipMethod($method) {
		// Check if the method name is a snake case.
		if (preg_match('/_/', $method->name->name)) {
			return true;
		}

		// Check if it takes arguments.
		if (property_exists($method, 'params') &&
			!is_null($method->params) &&
			is_array($method->params) &&
			count($method->params) > 0) {

			if (property_exists($method->params[0], 'default') &&
				!is_null($method->params[0]->default)) {
				return false;
			}
			return true;
		}

		return false;
	}

	private function procControllers(){
		$components = [];
		$component_main_models = [];
		$this->procComponents($components, $component_main_models);

		$model_tables = $this->procModelTables();

		$app_controller_file = glob($this->controller_dir . '/AppController.php');
		$controller_files    = glob($this->controller_dir . '/*.php');

		$controller_files    = array_diff($controller_files, $app_controller_file);
		array_unshift($controller_files, $app_controller_file[0]);
		//var_dump($controller_files);

		$app_controller_methods = [];
		$controller_method_hash = [];
		foreach ($controller_files as $controller_file) {
			if (!preg_match("/AppController\.php$/", $controller_file) &&
				!preg_match("/GroupsController\.php$/", $controller_file)) {
				//continue;
			}

			// Errors occur during parsing.
			if (preg_match("/ApProfilesController\.orig\.php$/", $controller_file) ||
			    preg_match("/NodeReportsController_Rabbit\.php$/", $controller_file) ||
			    preg_match("/NodeReportsController_Rabbit_MQ\.php$/", $controller_file)
			   ) {
				continue;
			}

			$code = file_get_contents($controller_file);
			$stmts = $this->parseCode($code);
			//var_dump($stmts);

			$class_name = $this->getClassName($stmts);
			$main_model = $this->getMainModel($stmts);

			$methods_hash = [];
			$class_methods = $app_controller_methods;
			$initialize_params = ['data' => [], 'query' => []];

			if ($this->checkSkipController($class_name)) {
				continue;
			}

			$methods = $this->getClassMethodNodes($stmts);
			//var_dump($methods);
			for ($i = 0; $i < 3; $i++) {
				foreach ($methods as $method) {
					$method_name = $method->name->name;
					$rm = new \ReflectionMethod('App\Controller\\' .
							  $class_name . "::" . $method_name);
					if (($i > 0  && !$rm->isPublic()) ||
						($i == 1 && $method_name != 'initialize') ||
						($i == 2 && $method_name == 'initialize')) {
						continue;
					}

					//var_dump($method->stmts);
					if ($class_name == 'AppController' && !$rm->isPrivate()) {
						$app_controller_methods[$method_name] = $method;
					}
					$class_methods[$method_name] = $method;

					if ($i > 0 && $rm->isPublic()) {
						if ($method_name != 'initialize') {
							if ($this->checkSkipMethod($method)) {
								//printf("%s::%s is skipped\n", $class_name, $method_name);
								continue;
							}

							if ($class_name == 'GroupsController' &&
								$method_name != 'view') {
								//continue;
							}
						}

						//printf("%s::%s -> start\n", $class_name, $method_name);

						// Obtain http method information.
						$http_methods = $this->getUsableHttpMethods($method->stmts);

						// Gets access to the request.
						$params = $this->getRequestParams($method->stmts);
						//var_dump($params);

						// Get access to the request in the method being called.
						$params = $this->mergeParams($params,
									$this->procMethodCalls($class_name,
														   $method_name,
														   $components,
														   $model_tables,
														   $class_methods,
														   $main_model,
														   $component_main_models,
														   $method->stmts));

						if ($method_name == 'initialize') {
							// AppController::initialize() rewrites the token query,
							// but does not enumerate it as a parameter because
							// it is not required.

							if ($class_name != 'AppController') {
								$initialize_params = $params;
							}
						} else {
							$params = $this->mergeParams($params, $initialize_params);
							$methods_hash[$method_name] = [ 
								'params'		=> $params,
								'http_methods'	=> $http_methods,
							];
						}

						//printf("%s::%s -> finish\n", $class_name, $method_name);
					}
				}
			}

			if ($class_name != 'AppController') {
				$controller_method_hash[$class_name] = $methods_hash;
			}
		}
		return $controller_method_hash;
	}

	private function convClassNameToApiPath($class_name)
	{
		$api_path = preg_replace("/Controller$/", '', $class_name);
		$api_path = ltrim(strtolower(preg_replace(
						'/[A-Z]([A-Z](?![a-z]))*/', '-$0', $api_path)), '-');
		$api_path = '/cake3/rd_cake/' . $api_path . '/';
		return $api_path;
	}

	private function convFuncNameToApiFile($func_name)
	{
		$api_file = $func_name;
		$api_file = ltrim(strtolower(
			 			preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', $api_file)), '-');
		$api_file .= '.json';
		return $api_file;
	}

	private function convToApiApth($class_name, $method_name) {
		return $this->convClassNameToApiPath($class_name) .
				$this->convFuncNameToApiFile($method_name);
	}

	private function convTableNameToModelName($table_name)
	{
		return strtr(ucwords(strtr($table_name, ['_' => ' '])), [' ' => '']);
	}

	private function dumpApiPath($controller_method_hash) {
		foreach ($controller_method_hash as $class_name => $methods) {
			print $class_name;
			print "\n";

			$methods_list = array_keys($methods);
			asort($methods_list);

			foreach ($methods_list as $method_name) {
				$api_path = $this->convToApiApth($class_name, $method_name);
				print "  ";
				print $api_path;

				$http_methods = $methods[$method_name]['http_methods'];
				if (count($http_methods) > 0 && count($http_methods) < 5) {
					printf(" (%s)", join(',', $http_methods));
				}
				print "\n";

				$params = $methods[$method_name]['params'];
				foreach ($params as $key => $values) {
					if (count($values) == 0) {
						continue;
					}
					if ($key == 'query') {
						if (!in_array('GET', $http_methods)) {
							continue;
						}
					} else if ($key == 'data') {
						if (!in_array('POST', $http_methods) &&
							!in_array('PUT', $http_methods) &&
							!in_array('DELETE', $http_methods) &&
							!in_array('PATCH', $http_methods)) {
							continue;
						}
					} else {
						continue;
					}

					printf("    %s ->\n", $key);
					foreach ($values as $value) {
						printf("      %s\n", $value);
					}
				}
				print "\n";
			}
			print "\n";
		}
	}

	private function determineHttpMethod($method) {
		$http_methods = $method['http_methods'];
		if (count($http_methods) == 0) {
			return null;
		} else if (count($http_methods) == 1) {
			return $http_methods[0];
		} else {
			$params = $method['params'];
			$query_count = -1;
			$data_count = -1;

			if (isset($params['query'])) {
				if (in_array('GET', $http_methods)) {
					$query_count = count($params['query']);
				}
			}

			if (isset($params['data'])) {
				if (in_array('POST', $http_methods) ||
					in_array('PUT', $http_methods) ||
					in_array('DELETE', $http_methods) ||
					in_array('PATCH', $http_methods)) {
					$data_count = count($params['data']);
				}
			}

			//printf("query_count: %d, data_count: %d\n", $query_count, $data_count);

			if ($query_count == -1 && $data_count == -1) {
				return null;
			} else if ($query_count > $data_count) {
				return 'GET';
			} else {
				foreach (['POST', 'PUT', 'DELETE', 'PATCH'] as $method_name) {
					if (in_array($method_name, $http_methods)) {
						return $method_name;
					}
				}
			}
		}
	}

	private function convertToOpenApi($controller_method_hash) {
		$open_api_obj = ['paths' => []];

		foreach ($controller_method_hash as $class_name => $methods) {
			$methods_list = array_keys($methods);
			asort($methods_list);

			foreach ($methods_list as $method_name) {
				$open_api_method_obj = [];

				$method = $methods[$method_name];
				$http_method = $this->determineHttpMethod($method);
				if (!isset($http_method)) {
					continue;
				}

				$api_path = $this->convToApiApth($class_name, $method_name);

				preg_match('/\/([\w\-]+)\/$/', 
						   $this->convClassNameToApiPath($class_name), $tags);
				if (count($tags) <= 1) {
					continue;
				}
				$open_api_method_obj['tags'] = [$tags[1]];

				$paramegers_obj = [];
				if ($http_method == 'GET') {
					$open_api_method_obj['parameters'] = [];

					$params = $method['params']['query'];
					foreach ($params as $param) {
						if ($param == 'token') {
							continue;
						}

						if ($param == 'id' || $param == '(numerical value)' ||
						    preg_match('/_id$/', $param)) {
							$type = 'integer';
							$example = 1;
						} else {
							$type = 'string';
							$example = '';
						}

						$open_api_method_obj['parameters'][] = [
							'name'			=> $param,
							'in'			=> 'query',
							'schema'		=> ['type' => $type],
							'example'		=> $example,
							'description'	=> '',
							'required'		=> false,
						];
					}

					$open_api_method_obj['responses'][200]['description'] = '';
					$open_api_method_obj['responses'][200]['content']['application/json']
										['schema']['$ref'] = '#/components/schemas/ListItems';
					$open_api_method_obj['responses']['default']['description'] = '';
					$open_api_method_obj['responses']['default']['content']['application/json']
										['schema']['$ref'] = '#/components/schemas/TokenError';
				} else {
					$open_api_method_obj['parameters'] = [];
					$open_api_method_obj['requestBody']['content']
										['application/x-www-form-urlencoded']
										['schema']['type'] = 'object';

					$open_api_request_body_properties = [];
					$params = $method['params']['data'];
					foreach ($params as $param) {
						if ($param == 'token') {
							continue;
						}

						if ($param == 'id' || $param == '(numerical value)' ||
						    preg_match('/_id$/', $param)) {
							$type = 'integer';
							$example = 1;
						} else {
							$type = 'string';
							$example = '';
						}

						$open_api_request_body_properties[$param] = [
							'type'			=> $type,
							'example'		=> $example,
							'description'	=> '',
						];
					}
					$open_api_method_obj['requestBody']['content']
										['application/x-www-form-urlencoded']
										['schema']['properties'] =
										$open_api_request_body_properties;

					$open_api_method_obj['responses'][200]['description'] = '';
					$open_api_method_obj['responses'][200]['content']['application/json']
										['schema']['$ref'] =
										'#/components/schemas/SuccessOnly';
					$open_api_method_obj['responses']['default']['description'] = '';
					$open_api_method_obj['responses']['default']['content']['application/json']
										['schema']['$ref'] =
										'#/components/schemas/OutputErrorCause';
				}

				$open_api_obj['paths'][$api_path][strtolower($http_method)] =
					$open_api_method_obj;
			}
		}

		$yaml_str = yaml_emit($open_api_obj);
		$yaml_str = preg_replace('/properties: \[\]/', 'properties: {}', $yaml_str);
		$yaml_str = preg_replace('/\$ref: \'(.*)\'/', '$ref: "\1"', $yaml_str);
		print $yaml_str;
	}

	private function getModelColumns($model_name) {
		//printf("model_name: %s\n", $model_name);

		// XXX: Excluded due to errors.
		if ($model_name == "PhraseKeys" ||
			$model_name == "PhraseValues") {
			printf("model %s is skipped!\n", $model_name);
			return [];
		}

        try {
			$model = $this->loadModel($model_name);
			$columns = $model->schema()->columns();
		} catch (\Exception $e) {
			echo $e->getMessage();
			echo "\n";
			return [];
		}

		asort($columns);
		$columns = array_unique($columns);
		$columns = array_values($columns);
		return $columns;
	}

	private function segregateModels($controller_method_hash) {
		$used_models = [];
		$not_used_models = [];

		// Get all tables.
		$models = [];
		$tables = ConnectionManager::get('default')->schemaCollection()->listTables();
		foreach ($tables as $table) {
			$models[] = $this->convTableNameToModelName($table);
		}

		// Segregate database tables.
		foreach ($controller_method_hash as $class_name => $methods) {
			$methods_list = array_keys($methods);
			asort($methods_list);

			foreach ($methods_list as $method_name) {
				$method_models = $methods[$method_name]['params']['model'];

				foreach ($method_models as $method_model) {
					if (!array_key_exists($method_model, $used_models)) {
						$used_models[$method_model] = [];
					}
					if (!in_array($class_name, $used_models[$method_model])) {
						$used_models[$method_model][] = $class_name;
					}
				}
			}
		}

		print "Used models:\n";
		$used_model_keys = array_keys($used_models);
		sort($used_model_keys);
		foreach ($used_model_keys as $used_model) {
			$class_names = $used_models[$used_model];
			print "  $used_model";
			if (!in_array($used_model, $models)) {
				print " (table not found)";
			}
			print "\n";

			$line = "";
			foreach ($class_names as $class_name) {
				if (strlen($line) + strlen($class_name) + 2 > 100) {
					print $line.",\n";
					$line = "";
				}

				if (strlen($line) == 0) {
					$line  = sprintf("    %s", $class_name);
				} else {
					$line .= sprintf(", %s", $class_name);
				}
			}
			if (strlen($line) > 0) {
				print $line."\n";
			}
		}
		print "\n";

		$not_used_models = array_diff($models, array_keys($used_models));
		print "Not used models:\n";
		foreach ($not_used_models as $not_used_model) {
			print "  $not_used_model\n";
		}
	}

	private function testApiPath($controller_method_hash) {
		$url_base = 'http://172.25.0.25';

		foreach ($controller_method_hash as $class_name => $methods) {
			$methods_list = array_keys($methods);
			asort($methods_list);

			foreach ($methods_list as $method_name) {
				$api_path = $this->convToApiApth($class_name, $method_name);
				$api_url = $url_base . $api_path;
				$command = sprintf("curl -s -G %s", $api_url);

				$output = "";
				$result_code = -1;
				exec($command, $output, $result_code);

				if ($result_code != 0) {
					printf("%s -> %d\n", $command, $result_code);
				}
			}
		}
	}

    public function main(){
		$controller_method_hash = $this->procControllers();
		print "\n";
		//var_dump($controller_method_hash);
        //exit;

		//$this->segregateModels($controller_method_hash);

		//$this->dumpApiPath($controller_method_hash);
		$this->convertToOpenApi($controller_method_hash);

		//$this->testApiPath($controller_method_hash);
    }
}

?>
