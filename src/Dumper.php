<?php

namespace Thruster\Component\Dns;

/**
 * Class Dumper
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Dumper
{
    public function toBinary(Message $message) : string
    {
        $data = '';

        $data .= $this->headerToBinary($message->header);
        $data .= $this->questionToBinary($message->questions);

        return $data;
    }

    private function headerToBinary(HeaderBag $header) : string
    {
        $data = '';

        $data .= pack('n', $header->get('id'));

        $flags = 0x00;
        $flags = ($flags << 1) | $header->get('qr');
        $flags = ($flags << 4) | $header->get('opcode');
        $flags = ($flags << 1) | $header->get('aa');
        $flags = ($flags << 1) | $header->get('tc');
        $flags = ($flags << 1) | $header->get('rd');
        $flags = ($flags << 1) | $header->get('ra');
        $flags = ($flags << 3) | $header->get('z');
        $flags = ($flags << 4) | $header->get('rcode');

        $data .= pack('n', $flags);

        $data .= pack('n', $header->get('qdCount'));
        $data .= pack('n', $header->get('anCount'));
        $data .= pack('n', $header->get('nsCount'));
        $data .= pack('n', $header->get('arCount'));

        return $data;
    }

    private function questionToBinary(array $questions) : string
    {
        $data = '';

        /** @var Query $question */
        foreach ($questions as $question) {
            $labels = explode('.', $question->getName());
            foreach ($labels as $label) {
                $data .= chr(strlen($label)).$label;
            }
            $data .= "\x00";

            $data .= pack('n*', $question->getType(), $question->getClass());
        }

        return $data;
    }
}
