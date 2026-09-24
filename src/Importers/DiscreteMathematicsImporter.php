<?php

declare(strict_types=1);

final class DiscreteMathematicsImporter
{
    private const SOURCE_URL =
    'https://wiki.cs.hse.ru/%D0%94%D0%B8%D1%81%D0%BA%D1%80%D0%B5%D1%82%D0%BD%D0%B0%D1%8F_%D0%9C%D0%B0%D1%82%D0%B5%D0%BC%D0%B0%D1%82%D0%B8%D0%BA%D0%B0_%D0%9A%D0%9D%D0%90%D0%94_2026/27?action=raw';

    public function sourceUrl(): string
    {
        return self::SOURCE_URL;
    }

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
                'Could not download Discrete Mathematics wiki: '
                    . $error
            );
        }

        $statusCode = curl_getinfo(
            $curl,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($curl);

        if ($statusCode !== 200) {
            throw new RuntimeException(
                'Discrete Mathematics wiki returned HTTP '
                    . $statusCode
            );
        }

        return $content;
    }

    public function parseHomeworkForGroup(
        string $content,
        int $group
    ): array {
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

        $sectionPattern =
            "/'''ДЗ для "
            . preg_quote((string) $group, '/')
            . "-й группы:'''"
            . "(.*?)"
            . "(?=\n\s*'''ДЗ для \d+-й группы:'''"
            . "|\n\s*==|\z)/su";

        if (!preg_match(
            $sectionPattern,
            $content,
            $sectionMatch
        )) {
            throw new RuntimeException(
                'Homework section not found for group '
                    . $group
                    . '.'
            );
        }

        $section = $sectionMatch[1];

        $homeworkPattern =
            "/^\s*\*\s*"
            . "\[(https?:\/\/[^\s\]]+)\s+"
            . "'''ДЗ\s*№\s*(\d+)'''\]"
            . "\s*\("
            . "выдача:\s*(\d{1,2}\.\d{1,2}\.\d{2,4}),"
            . "\s*дедлайн:\s*(\d{1,2}\.\d{1,2}\.\d{2,4})"
            . "\)/mu";

        preg_match_all(
            $homeworkPattern,
            $section,
            $matches,
            PREG_SET_ORDER
        );

        $homeworks = [];

        foreach ($matches as $match) {
            $homeworks[] = [
                'number' => (int) $match[2],
                'assignment_url' => $match[1],
                'issued_date' => $match[3],
                'deadline_date' => $match[4],
            ];
        }

        usort(
            $homeworks,
            static fn(array $a, array $b): int =>
            $a['number'] <=> $b['number']
        );

        return $homeworks;
    }
}
