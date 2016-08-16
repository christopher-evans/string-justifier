<?php

namespace Cev\Justifier;

/**
 * Class StringJustifier
 *
 * Class for justifying a string to a particular width
 *
 * @author   Christopher Evans <c.m.evans@gmx.co.uk>
 * @version  1.0.0
 */
class StringJustifier
{
    /**
     * Justification width
     *
     * @var int
     */
    protected $width = 32;

    /**
     * Maximum spacing between words
     *
     * @var int
     */
    protected $maxSpace = 3;

    /**
     * Line separator
     *
     * @var string
     */
    protected $lineSeparator = PHP_EOL;

    /**
     * Paragraph separator
     *
     * @var string
     */
    protected $paragraphSeparator;

    /**
     * Word break
     *
     * @var string
     */
    protected $wordBreak = '-';

    /**
     * Justifier constructor.
     *
     * @param string|null $lineSeparator
     * @param string|null $wordBreak
     * @param string|null $paragraphSeparator
     */
    public function __construct(
        $lineSeparator = null,
        $wordBreak = null,
        $paragraphSeparator = null
    ) {
        //set line separator
        if (is_string($lineSeparator)) {
            $this->lineSeparator = $lineSeparator;
        }

        //set word break
        if (is_string($wordBreak)) {
            $this->wordBreak = $wordBreak;
        }

        //set paragraph separator
        if (is_string($paragraphSeparator)) {
            $this->paragraphSeparator = $paragraphSeparator;
        } else {
            //set here for php 5.3 compatibility
            $this->paragraphSeparator = PHP_EOL . PHP_EOL;
        }
    }

    /**
     * Magic method
     *
     * @param string $string
     * @param int    $width
     * @param int    $maxSpace
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function __invoke($string, $width = 32, $maxSpace = 3)
    {
        //defer to ::format
        return $this->format($string, $width, $maxSpace);
    }

    /**
     * Justify a body of text
     *
     * @param string $string
     * @param int    $width
     * @param int    $maxSpace
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function format($string, $width = 32, $maxSpace = 3)
    {
        //set width
        if (is_int($width) && ($width > 0)) {
            //ensure width is set
            $this->width = $width;
        }

        //set max space
        if (is_int($maxSpace) && ($maxSpace > 0)) {
            //ensure width is set
            $this->maxSpace = $maxSpace;
        }

        //validate string
        if (!is_string($string)) {
            throw new \InvalidArgumentException('Argument $string must be a string');
        }

        //single paragraph
        if (!$this->paragraphSeparator) {
            return $this->justifyParagraph($string);
        }

        //break into paragraphs
        $paragraphs = explode($this->paragraphSeparator, $string);
        foreach ($paragraphs as $index => $paragraph) {
            $paragraphs[$index] = $this->justifyParagraph($paragraph);
        }

        //glue back together
        return implode($this->paragraphSeparator, $paragraphs);
    }

    /**
     * Justify a single paragraph of text
     *
     * @param string $string
     *
     * @return string
     */
    protected function justifyParagraph($string)
    {
        //clean string
        $string = $this->cleanString($string);

        if (($string === '') || (mb_strlen($string) <= $this->width)) {
            //nothing to do
            return $string;
        }

        //get string chunks
        $chunks = $this->getStringChunks($string);

        //justify chunks
        $lastChunk       = count($chunks) - 1;
        $lastChunkLength = mb_strlen($chunks[$lastChunk]);
        for ($i = 0; $i < $lastChunk; $i++) {
            $chunks[$i] = $this->justifyChunk($chunks[$i]);
        }

        //pad last chunk
        if ($lastChunkLength < $this->width) {
            $chunks[$lastChunk] = $chunks[$lastChunk] . str_repeat(' ', $this->width - $lastChunkLength);
        }

        //return
        return implode($this->lineSeparator, $chunks);
    }

    /**
     * Clean up string for justification
     *
     * @param $string
     *
     * @return string
     */
    protected function cleanString($string)
    {
        //trim
        $string = trim($string);

        //remove multiple spaces and return
        return preg_replace('/( )\\1+/', '$1', $string);
    }

    /**
     * Cut a string into chunks
     *
     * @param string $string
     *
     * @return array Array of chunks
     */
    protected function getStringChunks($string)
    {
        $chunks = array();
        $string = trim($string);

        while ($string !== '') {
            if (mb_strlen($string) <= $this->width) {
                $chunks[] = $string;

                break;
            }

            //find the space to break at
            $spacePosition = mb_strrpos($string, ' ', $this->width - mb_strlen($string));
            if ($spacePosition === false) {
                //space not found in the first $this->width characters

                //break off a chunk
                $chunks[] = mb_substr($string, 0, $this->width - 1) . $this->wordBreak;
                $string   = mb_substr($string, $this->width - 1);

                //next iteration
                continue;
            }

            //break off a chunk
            $chunk = trim(mb_substr($string, 0, $spacePosition));

            //check gaps don't exceed the max allowed space
            if ($this->maxSpace > 0) {
                $gaps    = mb_substr_count($chunk, ' ');
                //we might have lost a space so check for no gaps
                if ($gaps === 0) {
                    $gapSize = $this->width - $spacePosition;
                } else {
                    $gapSize = (int)ceil(($gaps + $this->width - mb_strlen($chunk)) / $gaps);
                }

                if ($gapSize > $this->maxSpace) {
                    //gaps exceed max allowed space
                    //break a chunk off the next word
                    //we know that the next word won't fit
                    //so skip any validation on that

                    //break off the chunk
                    $chunks[] = mb_substr($string, 0, $this->width - 1) . $this->wordBreak;
                    $string   = mb_substr($string, $this->width - 1);

                    //next iteration
                    continue;
                }
            }

            //now we have a space to break at
            //add a chunk to the array
            $chunks[] = $chunk;

            //chop this chunk off the string
            $string = trim(mb_substr($string, $spacePosition + 1));
        }

        //return
        return $chunks;
    }

    /**
     * Justify a string chunk
     *
     * @param string $chunk
     *
     * @return string Justified chunk
     */
    protected function justifyChunk($chunk)
    {
        if (mb_strlen($chunk) >= $this->width) {
            //keep the given justification
            //if > occurs here it's a bug
            //just present to be safe
            return $chunk;
        }

        if (mb_strlen($chunk) > $this->width) {
            //the chunk is a single word
            return $chunk;
        }

        return $this->padWithSpaces($chunk);
    }

    /**
     * Pad spaces in a chunk up to length
     *
     * @param string $chunk
     *
     * @return string Justified chunk
     */
    protected function padWithSpaces($chunk)
    {
        $totalSpaces      = $this->width - mb_strlen(str_replace(' ', '', $chunk));
        $words            = explode(' ', $chunk);
        $totalGaps        = count($words) - 1;
        if ($totalGaps < 1) {
            //no gaps to pad
            return $chunk;
        }

        $normalSpace      = (int)floor($totalSpaces / $totalGaps);
        $additionalSpaces = $totalSpaces % $totalGaps;

        for ($i = 0; $i < count($words) - 1; $i++) {
            $word = $words[$i];

            //add padding to word
            $word .= str_repeat(' ', $normalSpace);

            //add additional spaces
            if ($additionalSpaces > 0) {
                $word .= ' ';

                $additionalSpaces--;
            }

            $words[$i] = $word;
        }

        //join the string back together
        return implode('', $words);
    }
}
