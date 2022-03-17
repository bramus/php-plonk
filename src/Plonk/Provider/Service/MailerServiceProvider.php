<?php

namespace Plonk\Provider\Service;

class MailerServiceProvider extends PimpleBasedServiceProvider
{
	public function registerInPimple(\Pimple $app)
	{
		if (!$this->config) {
			return;
		}

		$mailerConfig = $this->config;
		$app['mailer'] = function() use ($app, $mailerConfig) {
			$mailer = new \PHPMailer\PHPMailer\PHPMailer(true);

			switch ($mailerConfig['mailer']) {
				case 'sendmail':
					$mailer->isSendmail();

					break;
				case 'smtp':
					$mailer->isSMTP();
					$mailer->Host = $mailerConfig['smtp']['host'];
					$mailer->Port = $mailerConfig['smtp']['port'];
					$mailer->SMTPAuth = $mailerConfig['smtp']['auth'];
					$mailer->Username = $mailerConfig['smtp']['username'];
					$mailer->Password = $mailerConfig['smtp']['password'];
					$mailer->SMTPSecure = $mailerConfig['smtp']['secure'];
					// $mailer->SMTPAutoTLS = false;
					$mailer->SMTPDebug = $mailerConfig['smtp']['debug'];

					break;
				default:
					throw new \Exception('Invalid “mailer” defined in $config["conf.mail"]');
			}

			$mailer->CharSet = 'UTF-8';

			return $mailer;
		};
	}

	public function bootInPimple(\Pimple $app)
	{
		// Nothing!
	}
}
