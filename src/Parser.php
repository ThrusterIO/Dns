<?php

namespace Thruster\Component\Dns;

/**
 * Class Parser
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Parser
{
    public function parseChunk($data, Message $message)
    {
        $message->data .= $data;

        if (null === $message->header->get('id')) {
            if (null === $this->parseHeader($message)) {
                return;
            }
        }

        if ($message->header->get('qdCount') != count($message->questions)) {
            if (null === $this->parseQuestion($message)) {
                return;
            }
        }

        if ($message->header->get('anCount') != count($message->answers)) {
            if (null === $this->parseAnswer($message)) {
                return;
            }
        }

        return $message;
    }

    public function parseHeader(Message $message) : Message
    {
        if (strlen($message->data) < 12) {
            return;
        }

        $header = substr($message->data, 0, 12);
        $message->consumed += 12;

        list($id, $fields, $qdCount, $anCount, $nsCount, $arCount) = array_values(unpack('n*', $header));

        $rcode  = $fields & bindec('1111');
        $z      = ($fields >> 4) & bindec('111');
        $ra     = ($fields >> 7) & 1;
        $rd     = ($fields >> 8) & 1;
        $tc     = ($fields >> 9) & 1;
        $aa     = ($fields >> 10) & 1;
        $opcode = ($fields >> 11) & bindec('1111');
        $qr     = ($fields >> 15) & 1;

        $vars = [
            'id'      => $id,
            'qdCount' => $qdCount,
            'anCount' => $anCount,
            'nsCount' => $nsCount,
            'arCount' => $arCount,
            'qr'      => $qr,
            'opcode'  => $opcode,
            'aa'      => $aa,
            'tc'      => $tc,
            'rd'      => $rd,
            'ra'      => $ra,
            'z'       => $z,
            'rcode'   => $rcode,
        ];

        foreach ($vars as $name => $value) {
            $message->header->set($name, $value);
        }

        return $message;
    }

    public function parseQuestion(Message $message)
    {
        if (strlen($message->data) < 2) {
            return;
        }

        $consumed = $message->consumed;

        list($labels, $consumed) = $this->readLabels($message->data, $consumed);

        if (null === $labels) {
            return;
        }

        if (strlen($message->data) - $consumed < 4) {
            return;
        }

        list($type, $class) = array_values(unpack('n*', substr($message->data, $consumed, 4)));
        $consumed += 4;

        $message->consumed = $consumed;

        $message->questions[] = [
            'name'  => implode('.', $labels),
            'type'  => $type,
            'class' => $class,
        ];

        if ($message->header->get('qdCount') != count($message->questions)) {
            return $this->parseQuestion($message);
        }

        return $message;
    }

    public function parseAnswer(Message $message) : Message
    {
        if (strlen($message->data) < 2) {
            return;
        }

        $consumed = $message->consumed;

        list($labels, $consumed) = $this->readLabels($message->data, $consumed);

        if (null === $labels) {
            return;
        }

        if (strlen($message->data) - $consumed < 10) {
            return;
        }

        list($type, $class) = array_values(unpack('n*', substr($message->data, $consumed, 4)));
        $consumed += 4;

        list($ttl) = array_values(unpack('N', substr($message->data, $consumed, 4)));
        $consumed += 4;

        list($rdLength) = array_values(unpack('n', substr($message->data, $consumed, 2)));
        $consumed += 2;

        $rdata = null;

        if (Message::TYPE_A === $type) {
            $ip = substr($message->data, $consumed, $rdLength);
            $consumed += $rdLength;

            $rdata = inet_ntop($ip);
        }

        if (Message::TYPE_CNAME === $type) {
            list($bodyLabels, $consumed) = $this->readLabels($message->data, $consumed);

            $rdata = implode('.', $bodyLabels);
        }

        $message->consumed = $consumed;

        $name   = implode('.', $labels);
        $ttl    = $this->signedLongToUnsignedLong($ttl);
        $record = new Record($name, $type, $class, $ttl, $rdata);

        $message->answers[] = $record;

        if ($message->header->get('anCount') != count($message->answers)) {
            return $this->parseAnswer($message);
        }

        return $message;
    }

    private function readLabels($data, $consumed)
    {
        $labels = [];

        while (true) {
            if ($this->isEndOfLabels($data, $consumed)) {
                $consumed += 1;
                break;
            }

            if ($this->isCompressedLabel($data, $consumed)) {
                list($newLabels, $consumed) = $this->getCompressedLabel($data, $consumed);
                $labels = array_merge($labels, $newLabels);
                break;
            }

            $length = ord(substr($data, $consumed, 1));
            $consumed += 1;

            if (strlen($data) - $consumed < $length) {
                return [null, null];
            }

            $labels[] = substr($data, $consumed, $length);
            $consumed += $length;
        }

        return [$labels, $consumed];
    }

    public function isEndOfLabels($data, $consumed) : bool
    {
        $length = ord(substr($data, $consumed, 1));

        return 0 === $length;
    }

    public function getCompressedLabel($data, $consumed) : array
    {
        list($nameOffset, $consumed) = $this->getCompressedLabelOffset($data, $consumed);
        list($labels) = $this->readLabels($data, $nameOffset);

        return [$labels, $consumed];
    }

    public function isCompressedLabel($data, $consumed) : bool
    {
        $mask = 0xc000; // 1100000000000000
        list($peek) = array_values(unpack('n', substr($data, $consumed, 2)));

        return (bool)($peek & $mask);
    }

    public function getCompressedLabelOffset($data, $consumed) : array
    {
        $mask = 0x3fff; // 0011111111111111
        list($peek) = array_values(unpack('n', substr($data, $consumed, 2)));

        return [$peek & $mask, $consumed + 2];
    }

    public function signedLongToUnsignedLong($i) : int
    {
        return $i & 0x80000000 ? $i - 0xffffffff : $i;
    }
}
