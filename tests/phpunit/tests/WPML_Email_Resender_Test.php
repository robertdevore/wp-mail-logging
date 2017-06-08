<?php
use No3x\WPML\Tests\Helper\WPML_UnitTestCase;
use No3x\WPML\WPML_Email_Resender;

/**
 * Test resend feature.
 */

class WPML_Email_Resender_Test extends WPML_UnitTestCase {

    /** @var WPML_Email_Resender $emailResender */
    private $emailResender;

    /** @var \No3x\WPML\WPML_Email_Dispatcher $dispatcherMock */
    private $dispatcherMock;

    /** @var \No3x\WPML\Model\WPML_Mail|\PHPUnit_Framework_MockObject_MockObject $mailMock */
    private $mailMock;

    function setUp() {
        parent::setUp();
        $this->dispatcherMock = self::getMockBuilder('No3x\WPML\WPML_Email_Dispatcher')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->emailResender = new WPML_Email_Resender($this->dispatcherMock);
        $this->mailMock = self::getMockBuilder('No3x\WPML\Model\WPML_Mail')
            ->disableOriginalConstructor()
            ->setMethods( array('get_headers', 'get_attachments') )
            ->getMock()
        ;
    }

    /**
     * The mail contents are stored with line breaks encoded as php string literals in the database.
     * When loading this mail from the database to resend it in it's original format
     * at least the headers need to be fixed. To do so the header string is split on the
     * line endings (encoded as string literals) into an array.
     * @dataProvider headersProvider
     * @param string $headers header as string
     * @param array $expectedHeaders expected header parsed as array
     */
    function test_resendHeaders($headers, $expectedHeaders) {

        $this->mailMock->expects(self::once())
            ->method('get_headers')
            ->willReturn($headers)
        ;

        $this->dispatcherMock->expects(self::once())
            ->method('dispatch')
            ->with($this->mailMock->get_receiver(), $this->mailMock->get_subject(), $this->mailMock->get_message(), $expectedHeaders, $this->mailMock->get_attachments())
        ;

        $this->emailResender->resendMail($this->mailMock);
    }

    function headersProvider() {
        return array(
            "withoutFrom" => array(
                "example@example.com,\\nReply-To: example@com,\\nBcc: example@example.com,\\nContent-type: text/html; charset=UTF-8",
                array(
                    "example@example.com",
                    "Reply-To: example@com",
                    "Bcc: example@example.com",
                    "Content-type: text/html; charset=UTF-8"
                )
            ),
            "withRN" => array(
                "example@example.com,\\r\\nReply-To: example@com",
                array(
                    "example@example.com",
                    "Reply-To: example@com",
                )
            ),
            "withFrom" => array(
                "From: \"example@example.com\" <example@example.de>,\\nContent-type: text/html; charset=UTF-8",
                array(
                    "From: \"example@example.com\" <example@example.de>",
                    "Content-type: text/html; charset=UTF-8"
                )
            )
        );
    }
}
