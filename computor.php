<?php

error_reporting(0);

function error($str)
{
	$result = "\x1b[31mError: \x1b[0m";
	$result .= $str;
	$result .= '!';
	echo $result . PHP_EOL;
}

function check_argv($str)
{
	$str = preg_replace('/[\s]+/', '', $str);

	// Check invalid characters in equation
	preg_match_all("/[^^\d*+-=X]+/", $str, $matches);
	if (!empty($matches[0])) {
		error('Invalid characters in equation');
		return 0;
	}

	$str = preg_replace('/[+|-]/', ' $0', $str);

	// More than one '=' signs or no one '=' sign wasn't found
	$arr = explode('=', $str);
	if (count($arr) != 2) {
		error("More than one '=' signs or no one '=' sign wasn't found");
		return 0;
	}

	// One side of equation is empty
	if (trim($arr[0]) == '' || trim($arr[1]) == '') {
		error("One side of equation is empty");
		return 0;
	}

	// No one 'X' wasn't found
	if (!substr_count($str, 'X')) {
		error("No one 'X' wasn't found");
		return 0;
	}

	$left_parts = explode(' ', trim($arr[0]));
	$right_parts = explode(' ', trim($arr[1]));

	// Is degree is numeric in left part of equation
	$degree = 0;
	foreach ($left_parts as $part) {
		if (preg_match("/X\^(.+)/", $part, $matches)) {
			if (is_numeric($matches[1])) {
				if ($matches[1] > $degree) $degree = $matches[1];
			} else {
				error('Degree is not a numeric value');
				return 0;
			}
		}
	}

	// Is degree is numeric in right part of equation
	foreach ($right_parts as $key => $part) {
		if (preg_match("/X\^(.+)/", $part, $matches)) {
			if (is_numeric($matches[1])) {
				if ($matches[1] > $degree) $degree = $matches[1];
			} else {
				error('Degree is not a numeric value');
				return 0;
			}
		}
		if ($part[0] == '-') $part[0] = '+';
		else if ($part[0] == '+') $part[0] = '-';
		else $part = '-' . $part;
		$right_parts[$key] = $part;
	}

	$left_parts = array_merge($left_parts, $right_parts);
	$without_x = '';
	foreach ($left_parts as $key => $part) {
		if (!preg_match("/X/", $part)) {
			$without_x = floatval($without_x) + floatval($part);
			unset($left_parts[$key]);
		}
	}
	sort($left_parts);
	$equation = array();
	foreach ($left_parts as $key => $part) {
		if (preg_match("/X\^(\d+)/", $part, $matches)) {
			$value = str_replace('*', '', preg_replace("/X\^(\d+)/", "", $part));
			if (isset($equation['X^' . intval($matches[1])])) {
				$equation['X^' . intval($matches[1])] = floatval($equation['X^' . intval($matches[1])]) + floatval($value);
			} else {
				$equation['X^' . intval($matches[1])] = floatval($value);
			}
			unset($left_parts[$key]);
		}
	}

	if (!empty($left_parts)) {
		error('Incorrect form of equation');
		return 0;
	}
	ksort($equation);
	if (!empty($without_x)) $reduced_form = $without_x;
	else $reduced_form = null;
	foreach ($equation as $key => $value) {
		if (floatval($value) >= 0 && !empty($reduced_form)) $reduced_form .= '+';
		$reduced_form .= $value . ' * ' . $key;
	}
	$reduced_form = preg_replace('/[+|-]/', ' $0 ', $reduced_form); // reduced form
	return [$degree, $reduced_form];
}

function find_solution($degree, $reduced_form)
{
	echo 'Reduced form: ' . $reduced_form . ' = 0' . PHP_EOL;
	echo 'Polynomial degree: ' . $degree . PHP_EOL;
	if ($degree > 2) {
		echo 'The polynomial degree is stricly greater than 2, I can\'t solve.' . PHP_EOL;
		return;
	}
	$solution = "Solution: ";

	// замена X^0 на 1, пересчет коэффициента без Х и получение новой формы $reduced_form
	if (preg_match("/X\^0/", $reduced_form)) {
		$reduced_form = preg_replace('/ \* X\^0/', '', $reduced_form);
		$reduced_form = str_replace(' ', '', $reduced_form);
		$reduced_form = preg_replace('/[+|-]/', ' $0', $reduced_form);
		$reduced_form = explode(' ', $reduced_form);
		$without_x = '';
		foreach ($reduced_form as $key => $part) {
			if (!preg_match("/X/", $part)) {
				$without_x = floatval($without_x) + floatval($part);
				unset($reduced_form[$key]);
			}
		}
		sort($reduced_form);
		$equation = array();
		foreach ($reduced_form as $key => $part) {
			if (preg_match("/X\^(\d+)/", $part, $matches)) {
				$value = str_replace('*', '', preg_replace("/X\^(\d+)/", "", $part));
				if (isset($equation['X^' . intval($matches[1])])) {
					$equation['X^' . intval($matches[1])] = floatval($equation['X^' . intval($matches[1])]) + floatval($value);
				} else {
					$equation['X^' . intval($matches[1])] = floatval($value);
				}
				unset($reduced_form[$key]);
			}
		}
		ksort($equation);
		$reduced_form = $without_x;
		foreach ($equation as $key => $value) {
			if (floatval($value) >= 0 && !empty($reduced_form)) $reduced_form .= '+';
			$reduced_form .= $value . ' * ' . $key;
		}
		$reduced_form = preg_replace('/[+|-]/', ' $0 ', $reduced_form); // reduced form
	}

	echo "1). " . trim($reduced_form) . " = 0" . PHP_EOL;

	if ($degree == 0) {
		if (floatval($reduced_form) == (float)0) echo $solution . "X ∈ (-∞; +∞)" . PHP_EOL;
		else echo $solution . "X = ∅" . PHP_EOL;
	} elseif ($degree == 1) {
		$reduced_form = str_replace(' ', '', $reduced_form);
		preg_match('/([-]?\d+\.?\d*)([+|-]\d+\.?\d*)/', $reduced_form, $matches);
		if (floatval($matches[2]) == (float)0) {
			if (floatval($matches[1]) == (float)0) {
				echo $solution . "X ∈ (-∞; +∞)" . PHP_EOL;
			} else {
				echo "2). X = " . floatval($matches[1] * (-1)) . " / " . floatval($matches[2]) . PHP_EOL;
				echo $solution . "X = ∅" . PHP_EOL;
			}
		} else {
			echo "2). X = " . floatval($matches[1] * (-1)) . " / " . floatval($matches[2]) . PHP_EOL;
			echo $solution . "X = " . floatval($matches[1] / ($matches[2] * (-1))) . PHP_EOL;
		}
	} elseif ($degree == 2) {
		$reduced_form = str_replace(' ', '', $reduced_form);
		if (preg_match("/X\^2/", $reduced_form) && preg_match("/X\^1/", $reduced_form)) {
			$reduced_form = preg_replace('/[+|-]/', ' $0 ', $reduced_form);
			$arr = explode(' ', $reduced_form);
			$c = floatval($arr[0]);
			$b = ($arr[1] == '-') ? floatval(floatval($arr[2]) * (-1)) : floatval($arr[2]);
			$a = ($arr[3] == '-') ? floatval(floatval($arr[4]) * (-1)) : floatval($arr[4]);
			$discrim = $b * $b - 4 * $a * $c;
			echo "2). D = b^2 - 4 * a * c" . PHP_EOL;
			echo "	D = " . $b * $b . " - 4 * (" . $a . ") * (" . $c . ")" . PHP_EOL;
			echo "	D = " . $discrim . PHP_EOL;
			if ($discrim > 0) {
				echo "Discriminant is strictly positive" . PHP_EOL;
				echo "   √D = " . sqrt($discrim) . PHP_EOL;
				echo "3). The two solutions are:" . PHP_EOL;
				echo "x1,x2 = (- b ± √D)/ (2 * a)" . PHP_EOL;
				echo "x1 = " . floatval(((-1 * $b) + sqrt($discrim)) / (2 * $a)) . PHP_EOL;
				echo "x2 = " . floatval(((-1 * $b) - sqrt($discrim)) / (2 * $a)) . PHP_EOL;
			} elseif ($discrim < 0) {
				echo "Discriminant is strictly negative" . PHP_EOL;
				echo "   √D = " . sqrt(-1 * $discrim) . "i" . PHP_EOL;
				echo "3). The two solutions are:" . PHP_EOL;
				echo "x1,x2 = (- b ± √D) / (2 * a)" . PHP_EOL;
				echo "x1 = " . floatval((-1 * $b) / (2 * $a)) . "+" . floatval(sqrt(-1 * $discrim) / (2 * $a)) . 'i' . PHP_EOL;
				echo "x2 = " . floatval((-1 * $b) / (2 * $a)) . '-' . floatval(sqrt(-1 * $discrim) / (2 * $a)) . 'i' . PHP_EOL;
			} else {
				echo "Discriminant is equal zero" . PHP_EOL;
				echo "3). The one solutions is:" . PHP_EOL;
				echo "x = " . floatval((-1 * $b) / (2 * $a)) . PHP_EOL;
			}
		} else {
			preg_match('/([-]?\d+\.?\d*)([+|-]\d+\.?\d*)/', $reduced_form, $matches);
			if (floatval($matches[2]) == (float)0) {
				if (floatval($matches[1]) == (float)0) {
					echo $solution . "X ∈ (-∞; +∞)" . PHP_EOL;
				} else {
					echo "2). X = ±√(" . floatval($matches[1] * (-1)) . " / " . floatval($matches[2]) . ')' . PHP_EOL;
					echo $solution . "X = ∅" . PHP_EOL;
				}
			} else {
				$in_power = floatval($matches[1] * (-1)) / floatval($matches[2]);
				if ($in_power > 0) {
					echo "2). X = ±√(" . $in_power . ')' . PHP_EOL;
					echo $solution . "X = ±" . sqrt($in_power) . PHP_EOL;
				} else {
					echo "2). X = ±√(" . $in_power . ')' . PHP_EOL;
					echo $solution . "X = ±" . sqrt(-1 * $in_power) . 'i' . PHP_EOL;
				}
			}
		}
	}
}

if ($argc == 1) {
	error('Too few arguments');
	exit();
} else {
	array_shift($argv);
	foreach ($argv as $key => $equation) {
		$colors = ['32', '33', '34', '35', '36'];
		$index = array_rand($colors);
		echo "\033[01;{$colors[$index]}m Equation #" . ($key + 1) . "\e[0m" . PHP_EOL;
		$params = check_argv($equation);
		if (!empty($params)) {
			find_solution($params[0], $params[1]);
		}
		echo "\033[01;{$colors[$index]}m End Equation \e[0m" . PHP_EOL;
	}
	exit();
}

/*
 * Bonuses:
 * 1. Error management.
 * 2. Irrationals.
 * 3. Intermediate steps.
 * 4. Many equations
 */