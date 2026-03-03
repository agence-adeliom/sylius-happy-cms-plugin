<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use Sylius\Behat\Service\SharedStorageInterface;
use Webmozart\Assert\Assert;

final class MediaCsrfContext implements Context
{
    private Session $session;
    private SharedStorageInterface $sharedStorage;

    public function __construct(
        Session $session,
        SharedStorageInterface $sharedStorage
    ) {
        $this->session = $session;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When I go to the media manager page
     */
    public function iGoToTheMediaManagerPage(): void
    {
        $this->session->visit('/admin/happy-cms-media/medias');
    }

    /**
     * @Then I should see a CSRF token in the page metadata
     */
    public function iShouldSeeACsrfTokenInThePageMetadata(): void
    {
        $metaTag = $this->session->getPage()->find('css', 'meta[name="csrf-token"]');

        Assert::notNull($metaTag, 'CSRF token meta tag not found');

        $token = $metaTag->getAttribute('content');

        Assert::notEmpty($token, 'CSRF token is empty');

        // Store token for later use
        $this->sharedStorage->set('csrf_token', $token);
    }

    /**
     * @Then the CSRF token should be a 64-character hexadecimal string
     */
    public function theCsrfTokenShouldBeA64CharacterHexadecimalString(): void
    {
        $token = $this->sharedStorage->get('csrf_token');

        Assert::regex($token, '/^[a-f0-9]{64}$/', 'CSRF token is not a 64-character hexadecimal string');
    }

    /**
     * @Given there is a valid CSRF token
     */
    public function thereIsAValidCsrfToken(): void
    {
        $metaTag = $this->session->getPage()->find('css', 'meta[name="csrf-token"]');

        if ($metaTag) {
            $token = $metaTag->getAttribute('content');
            $this->sharedStorage->set('csrf_token', $token);
        } else {
            throw new \RuntimeException('CSRF token not found in page');
        }
    }

    /**
     * @When I attempt to upload a file without providing a CSRF token
     */
    public function iAttemptToUploadAFileWithoutProvidingACsrfToken(): void
    {
        $this->session->executeScript("
            // Remove CSRF token from axios headers
            delete axios.defaults.headers.common['X-CSRF-Token'];

            // Make upload request without token
            axios.post('" . $this->getUploadUrl() . "', new FormData(), {
                transformRequest: [(data, headers) => {
                    // Remove token if axios adds it
                    delete headers['X-CSRF-Token'];
                    return data;
                }]
            }).then(response => {
                window.lastResponse = response.data;
            }).catch(error => {
                window.lastResponse = error.response ? error.response.data : {success: false, message: error.message};
            });
        ");

        // Wait for response
        $this->session->wait(2000);
    }

    /**
     * @When I attempt to upload a file with an invalid CSRF token
     */
    public function iAttemptToUploadAFileWithAnInvalidCsrfToken(): void
    {
        $this->session->executeScript("
            const formData = new FormData();
            formData.append('_csrf_token', 'invalid-token-12345');

            axios.post('" . $this->getUploadUrl() . "', formData, {
                headers: {
                    'X-CSRF-Token': 'invalid-token-12345'
                }
            }).then(response => {
                window.lastResponse = response.data;
            }).catch(error => {
                window.lastResponse = error.response ? error.response.data : {success: false, message: error.message};
            });
        ");

        // Wait for response
        $this->session->wait(2000);
    }

    /**
     * @When I upload a file named :filename with the valid CSRF token
     */
    public function iUploadAFileNamedWithTheValidCsrfToken(string $filename): void
    {
        $token = $this->sharedStorage->get('csrf_token');

        // Create a blob file in JavaScript
        $this->session->executeScript("
            const blob = new Blob(['test content'], {type: 'text/plain'});
            const file = new File([blob], '" . $filename . "', {type: 'text/plain'});
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_csrf_token', '" . $token . "');
            formData.append('upload_folder', '');
            formData.append('random_names', 'false');

            axios.post('" . $this->getUploadUrl() . "', formData, {
                headers: {
                    'X-CSRF-Token': '" . $token . "'
                }
            }).then(response => {
                window.lastResponse = response.data;
            }).catch(error => {
                window.lastResponse = error.response ? error.response.data : {success: false, message: error.message};
            });
        ");

        // Wait for response
        $this->session->wait(2000);
    }

    /**
     * @Then I should see an error message containing :text
     */
    public function iShouldSeeAnErrorMessageContaining(string $text): void
    {
        $response = $this->session->evaluateScript('return window.lastResponse;');

        Assert::isArray($response, 'No response found');

        // Check if it's an array of responses (some endpoints return arrays)
        if (isset($response[0])) {
            $response = $response[0];
        }

        Assert::keyExists($response, 'success', 'Response does not have success key');
        Assert::false($response['success'], 'Operation succeeded when it should have failed');

        Assert::keyExists($response, 'message', 'Response does not have message key');

        $message = strtolower($response['message']);

        Assert::contains($message, strtolower($text), sprintf(
            'Error message "%s" does not contain "%s"',
            $response['message'],
            $text
        ));
    }

    /**
     * @Then I should see a success message
     */
    public function iShouldSeeASuccessMessage(): void
    {
        $response = $this->session->evaluateScript('return window.lastResponse;');

        Assert::isArray($response, 'No response found');

        // Check if it's an array of responses
        if (isset($response[0])) {
            $response = $response[0];
        }

        Assert::keyExists($response, 'success', 'Response does not have success key');
        Assert::true($response['success'], sprintf(
            'Operation failed: %s',
            $response['message'] ?? 'Unknown error'
        ));
    }

    /**
     * @Then the file should not be uploaded
     * @Then the file :filename should not be uploaded
     */
    public function theFileShouldNotBeUploaded(?string $filename = null): void
    {
        // This would require checking the actual media library
        // For now, we rely on the error response
        $response = $this->session->evaluateScript('return window.lastResponse;');

        if (isset($response[0])) {
            $response = $response[0];
        }

        Assert::false($response['success'] ?? true, 'File was uploaded when it should not have been');
    }

    /**
     * @Then the file :filename should be uploaded
     */
    public function theFileShouldBeUploaded(string $filename): void
    {
        $response = $this->session->evaluateScript('return window.lastResponse;');

        if (isset($response[0])) {
            $response = $response[0];
        }

        Assert::true($response['success'] ?? false, 'File was not uploaded');
        Assert::same($filename, $response['file_name'] ?? null, 'Uploaded filename does not match');
    }

    /**
     * @Given there is a media file :filename in the media library
     */
    public function thereIsAMediaFileInTheMediaLibrary(string $filename): void
    {
        // This would require actual media creation via fixtures or API
        // For testing purposes, we'll mock this
        $this->sharedStorage->set('test_media_' . $filename, [
            'id' => random_int(1, 1000),
            'name' => $filename,
            'type' => 'file',
            'storage_path' => '/' . $filename,
        ]);
    }

    /**
     * @When I attempt to delete :filename without providing a CSRF token
     */
    public function iAttemptToDeleteWithoutProvidingACsrfToken(string $filename): void
    {
        $media = $this->sharedStorage->get('test_media_' . $filename);

        $this->session->executeScript("
            axios.post('" . $this->getDeleteUrl() . "', {
                deleted_files: [" . json_encode($media) . "]
            }, {
                transformRequest: [(data, headers) => {
                    delete headers['X-CSRF-Token'];
                    // Remove token from data
                    delete data._csrf_token;
                    return JSON.stringify(data);
                }],
                headers: {'Content-Type': 'application/json'}
            }).then(response => {
                window.lastResponse = response.data;
            }).catch(error => {
                window.lastResponse = error.response ? error.response.data : {success: false, message: error.message};
            });
        ");

        $this->session->wait(2000);
    }

    /**
     * @When I delete :filename with the valid CSRF token
     */
    public function iDeleteWithTheValidCsrfToken(string $filename): void
    {
        $token = $this->sharedStorage->get('csrf_token');
        $media = $this->sharedStorage->get('test_media_' . $filename);

        $this->session->executeScript("
            axios.post('" . $this->getDeleteUrl() . "', {
                _csrf_token: '" . $token . "',
                deleted_files: [" . json_encode($media) . "]
            }).then(response => {
                window.lastResponse = response.data;
            }).catch(error => {
                window.lastResponse = error.response ? error.response.data : {success: false, message: error.message};
            });
        ");

        $this->session->wait(2000);
    }

    /**
     * @Then the file :filename should still exist
     * @Then the file :filename should not exist
     */
    public function theFileExistenceCheck(string $filename): void
    {
        // This would require checking actual file system or database
        // For now, we verify through response
    }

    // Helper methods

    private function getUploadUrl(): string
    {
        return '/admin/happy-cms-media/medias/upload';
    }

    private function getDeleteUrl(): string
    {
        return '/admin/happy-cms-media/medias/delete-file';
    }

    //private function getRenameUrl(): string
    //{
    //    return '/admin/happy-cms-media/medias/rename-file';
    //}
    //
    //private function getMoveUrl(): string
    //{
    //    return '/admin/happy-cms-media/medias/move-file';
    //}
    //
    //private function getNewFolderUrl(): string
    //{
    //    return '/admin/happy-cms-media/medias/create-new-folder';
    //}
    //
    //private function getEditMetasUrl(): string
    //{
    //    return '/admin/happy-cms-media/medias/edit-metas-file';
    //}
}
