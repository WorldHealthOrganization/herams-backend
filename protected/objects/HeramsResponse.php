<?php
declare(strict_types = 1);

namespace prime\objects;


use SamIT\LimeSurvey\Interfaces\ResponseInterface;

/**
 * Class HeramsResponse
 * @package prime\objects
 */
class HeramsResponse
{
    private static $surveySubjectKeys = [];
    private static $surveyArrayKeys = [];

    private $subjectExpression = '/^QHeRAMS\d+$/';
    /** @var array */
    private $data;
    /** @var HeramsCodeMap */
    private $map;

    private $surveyId;

    public function __construct(
        ResponseInterface $response,
        HeramsCodeMap $map
    ) {
        $this->data = $response->getData();
        $this->surveyId = $response->getSurveyId();
        $this->map = $map;

        // Validate.
        if (!isset($this->data[$this->map->getSubjectId()])) {
            throw new \InvalidArgumentException('Invalid response');
        }
    }

    public function getLatitude(): ?float
    {
        return ((float) $this->data[$this->map->getLatitude()]) ?? null;
    }

    public function getLongitude(): ?float
    {
        return ((float) $this->data[$this->map->getLongitude()]) ?? null;
    }

    public function getType(): ?string
    {
        return $this->data[$this->map->getType()] ?? null;
    }

    public function getDate(): ?\DateTimeInterface
    {
        if (null !== $date = $this->data[$this->map->getDate()] ?? null) {
            $result = \DateTime::createFromFormat('Y-m-d', explode(' ', $date, 2)[0]);
            if (!$result instanceof \DateTimeInterface) {
                throw new \RuntimeException('Invalid date format: ' . $date);
            }
            return $result;
        }
        return null;
    }

    public function getName(): ?string
    {
        return $this->data[$this->map->getName()] ?? null;
    }

    public function getValueForCode(string $code)
    {
        if (array_key_exists($code, $this->data)) {
            return $this->data[$code];
        }

        // Try iteration.
        $result = [];
        foreach($this->getKeysForCode($code) as $key) {
            if (isset($this->data[$key]) && !empty($this->data[$key])) {
                $result[] = $this->data[$key];
            }
        }
        return !empty($result) ? $result : null;
    }

    private function getKeysForCode(string $code)
    {
        if (!isset(self::$surveyArrayKeys[$this->surveyId][$code])) {
            self::$surveyArrayKeys[$this->surveyId][$code] = [];
            foreach ($this->data as $key => $dummy) {
                if (strpos($key . '[', $code) === 0) {
                    self::$surveyArrayKeys[$this->surveyId][$code][] = $key;
                }
            }
        }
        return self::$surveyArrayKeys[$this->surveyId][$code];
    }

    public function getSubjectId(): string
    {
        return $this->data[$this->map->getSubjectId()];
    }

    public function getLocation(): ?string
    {
        return $this->data[$this->map->getLocation()] ?? null;
    }


    private function getSubjectKeys()
    {
        if (!isset(self::$surveySubjectKeys[$this->surveyId])) {
            self::$surveySubjectKeys[$this->surveyId] = [];
            foreach($this->data as $key => $dummy) {
                if (preg_match($this->subjectExpression, $key)) {
                    self::$surveySubjectKeys[$this->surveyId][] = $key;
                }
            }
        }

        return self::$surveySubjectKeys[$this->surveyId];

    }
    /**
     * @return HeramsSubject[]
     */
    public function getSubjects(): iterable
    {
        foreach ($this->getSubjectKeys() as $key) {
            yield new HeramsSubject($this, $key);
        }
    }

    public function getSubjectAvailability()
    {
        $full = 0;
        $total = 0;
        foreach ($this->getSubjects() as $heramsSubject) {
            if ($heramsSubject->isFullyAvailable()) {
                $full++;
            }
            $total++;
        }

        return $total > 0 ? 100.0 * $full / $total : 0;
    }

    public function getMainReason(): ?string
    {
        $reasons = [];
        $services = [];
        foreach($this->data as $key => $value) {
            if (preg_match('/^(QHeRAMS\d+)x\[\d+\]$/', $key, $matches)) {
                if (empty($value)) {
                    continue;
                }
                $services[$matches[1]] = true;
                $reasons[$value] = ($reasons[$value] ?? 0) + 1;
            }
        }

        arsort($reasons);
        if (empty($reasons)) {
            return null;
        }
        $mainReason = array_keys($reasons)[0];
        return $mainReason;
        echo '<pre>';
        var_dump($reasons);
        $percentage = 1.0 * array_shift($reasons) / count($services);
        var_dump($mainReason, $services);

        var_dump($percentage); die();
    }


}