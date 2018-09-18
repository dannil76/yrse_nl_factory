<?php

namespace Yrse\Util;

/**
 * Email builder class.
 */
class Email
{
    /**
     * @var Yrse\Util\Command
     * @access private
     */
    private $command;

    /**
     * HTML body of the email
     *
     * @var string
     * @access private
     */
    private $body;

    /**
     * __construct function. You must pass a command object
     *
     * @access public
     * @param Yrse\Util\Command $command
     * @return void
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Set the HTML body of the email.
     *
     * @access public
     * @param string $html
     * @return void
     */
    public function setBody($html)
    {
        $this->body = $html;
    }

    /**
     * Set option.
     *
     * @access public
     * @param string $option
     * @param string $value
     * @return void
     */
    public function setOption($option, $value)
    {
        $this->command->setOption($option, $value);
    }

    /**
     * Get the HTML body of the Premailer'd email.
     *
     * @access public
     * @param string $baseUrl
     * @return string
     */
    public function getHtml($baseUrl)
    {
        return $this->command->getOutput(
            Command::HTML,
            $this->body,
            $baseUrl
        );
    }

    /**
     * Get the Text body of the Premailer'd email.
     *
     * @access public
     * @return string
     */
    public function getText()
    {
        return $this->command->getOutput(
            Command::TEXT,
            $this->body
        );
    }
}
