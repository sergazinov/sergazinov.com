<?php

declare(strict_types=1);

final class LinearAlgebraImporter
{
    private const SOURCE_URL =
    'https://wiki.cs.hse.ru/%D0%9B%D0%B8%D0%BD%D0%B5%D0%B9%D0%BD%D0%B0%D1%8F_%D0%B0%D0%BB%D0%B3%D0%B5%D0%B1%D1%80%D0%B0_%D0%9A%D0%9D%D0%90%D0%94_26/27?action=raw';

    public function fetch(): string
    {
        $curl = curl_init(self::SOURCE_URL);

        if ($curl === false) {
            throw new RuntimeException(
                'Could not initialize cURL.'
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'sergazinov.com HSE deadline importer',
        ]);

        $content = curl_exec($curl);

        if ($content === false) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new RuntimeException(
                'Could not download Linear Algebra wiki: ' . $error
            );
        }

        $statusCode = curl_getinfo(
            $curl,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($curl);

        if ($statusCode !== 200) {
            throw new RuntimeException(
                'Linear Algebra wiki returned HTTP '
                    . $statusCode
            );
        }

        return $content;
    }

    public function parseWeeks(string $content): array
    {
        // Remove commented-out old material.
        $content = preg_replace(
            '/<!--.*?-->/s',
            '',
            $content
        );

        if ($content === null) {
            throw new RuntimeException(
                'Could not clean wiki content.'
            );
        }

        preg_match_all(
            '/\{\{Линейная алгебра КНАД 26\/Неделя(.*?)\}\}/su',
            $content,
            $matches
        );

        $weeks = [];

        foreach ($matches[1] as $block) {
            $weeks[] = [
                'number' => $this->extractField(
                    $block,
                    'num'
                ),
                'topic' => $this->extractField(
                    $block,
                    'topic'
                ),
                'homework_url' => $this->extractField(
                    $block,
                    'sem-notes'
                ),
                'homework_tex_url' => $this->extractField(
                    $block,
                    'sem-notes-tex'
                ),
            ];
        }

        return $weeks;
    }

    private function extractField(
        string $block,
        string $field
    ): ?string {
        $pattern =
            '/^\|' .
            preg_quote($field, '/') .
            '[ \t]*=[ \t]*([^\r\n]*)$/mu';

        if (!preg_match($pattern, $block, $match)) {
            return null;
        }

        $value = trim($match[1]);

        return $value === '' ? null : $value;
    }
}
