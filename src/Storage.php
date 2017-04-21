<?php namespace KX\Template;

class Storage
{
    /**
     * Any data provided to Render
     * should be saved to this var
     *
     * [name][id] = data
     *
     * @var array
     */
    private $templateData = [];

    /**
     * list of unique template names
     *
     * @var array
     */
    private $usedTemplates = [];

    /** @var array - Draw time in ms*/
    private $drawTime = [];

    /**
     * Save used data to store
     *
     * @param $templateName
     * @param $id
     * @param $data
     */
    public function save($templateName, $id, $data)
    {
        if (!in_array($templateName, array_keys($this->usedTemplates))) {
            $this->usedTemplates[$templateName] = 1;
        } else {
            $this->usedTemplates[$templateName]++;
        }

        $this->templateData[$templateName][$id] = $data;
    }

    /**
     * Add benchmark time to results
     *
     * @param $templateName
     * @param $time
     */
    public function addToDrawTime($templateName, $time)
    {
        $this->drawTime[$templateName] += $time;
    }

    /**
     * @return array
     */
    public function getUsedTemplates(): array
    {
        return array_unique(array_keys($this->usedTemplates));
    }

    /**
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * @return array
     */
    public function getDrawTime(): array
    {
        return $this->drawTime;
    }
}