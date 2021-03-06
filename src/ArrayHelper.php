<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2019 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Helpers;

use InvalidArgumentException;
use SimpleXMLElement;
use Traversable;

/**
 * Class ArrayHelper.
 *
 * @package Berlioz\Helpers
 */
final class ArrayHelper
{
    /**
     * Is sequential array?
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isSequential(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        if (!array_key_exists(0, $array)) {
            return false;
        }

        $keys = array_keys($array);
        sort($keys);

        return $keys === range(0, count($array) - 1);
    }

    /**
     * Convert array to an XML element.
     *
     * @param $array
     * @param \SimpleXMLElement|null $root
     * @param string|null $rootName
     *
     * @return \SimpleXMLElement
     */
    public static function toXml($array, ?SimpleXMLElement $root = null, ?string $rootName = null): SimpleXMLElement
    {
        // Traversable or array
        if (!(is_array($array) || $array instanceof Traversable)) {
            throw new InvalidArgumentException('First argument must be an array or instance of \Traversable interface');
        }

        if (is_null($root)) {
            $root = new SimpleXMLElement(sprintf('<root/>'));
        }

        foreach ($array as $key => $value) {
            if (is_array($value) || $value instanceof Traversable) {
                if (static::isSequential($value)) {
                    static::toXml($value, $root, (string)$key);
                    continue;
                }

                static::toXml($value, $root->addChild((string)($rootName ?? $key)));
                continue;
            }

            $root->addChild((string)($rootName ?? $key), $value);
        }

        return $root;
    }

    /**
     * Merge two or more arrays recursively.
     *
     * Difference between native array_merge_recursive() is that
     * b_array_merge_recursive() do not merge strings values
     * into an array.
     *
     * @param array $arraySrc Array source
     * @param array[] $arrays Arrays to merge
     *
     * @return array
     */
    public static function mergeRecursive(array $arraySrc, array ...$arrays): array
    {
        foreach ($arrays as $array) {
            if (self::isSequential($arraySrc) || self::isSequential($array)) {
                $arraySrc = array_merge($arraySrc, $array);
                continue;
            }

            foreach ($array as $key => $value) {
                if (!array_key_exists($key, $arraySrc)) {
                    $arraySrc[$key] = $value;
                    continue;
                }

                if (is_array($arraySrc[$key]) && is_array($value)) {
                    $arraySrc[$key] = self::mergeRecursive($arraySrc[$key], $value);
                    continue;
                }

                $arraySrc[$key] = $value;
            }
        }

        return $arraySrc;
    }

    /**
     * Traverse array with path and get value.
     *
     * @param iterable $mixed Source
     * @param string $path Path
     *
     * @return mixed|null
     * @throws \InvalidArgumentException if first argument is not a traversable data
     */
    public static function traverseGet(&$mixed, string $path)
    {
        if (!is_iterable($mixed)) {
            throw new InvalidArgumentException('First argument must be a traversable mixed data');
        }

        $path = explode('.', $path);

        $temp = &$mixed;
        foreach ($path as $key) {
            if (!is_iterable($temp)) {
                return null;
            }

            $temp = &$temp[$key];
        }

        return $temp;
    }

    /**
     * Traverse array with path and set value.
     *
     * @param iterable $mixed Source
     * @param string $path Path
     * @param mixed $value Value
     *
     * @return bool
     * @throws \InvalidArgumentException if first argument is not a traversable data
     */
    public static function traverseSet(&$mixed, string $path, $value): bool
    {
        if (!is_iterable($mixed)) {
            throw new InvalidArgumentException('First argument must be a traversable mixed data');
        }

        $path = explode('.', $path);

        $temp = &$mixed;
        foreach ($path as $key) {
            if (!is_null($temp) && !is_iterable($temp)) {
                return false;
            }

            $temp = &$temp[$key];
        }
        $temp = $value;

        return true;
    }
}