<?php
use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\Operand;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Gregwar\Captcha\PhraseBuilderInterface;

require_once __DIR__ . '/vendor/autoload.php';

define('NAME', 'GenCaptcha');
define('VERSION', '1.0-alpha');

$getOpt = new GetOpt();

$getOpt->addOptions([
    Option::create('c', null, GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('Count of captchas to create'),
    Option::create('w', null, GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('Width, defaults to 150'),
    Option::create('h', null, GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('Height, defaults to 40'),
    Option::create(null, 'quality', GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('Jpeg quality. Defaults to 80'),
    Option::create(null, 'phrasecount', GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('Number of characters for the phrase. Defaults to 3'),
    Option::create(null, 'alphabet', GetOpt::REQUIRED_ARGUMENT)
        ->setDescription('Alphabet to use for the phrase. Defaults to 0123456789'),
    Option::create(null, 'ignore-all-effects', GetOpt::NO_ARGUMENT)
        ->setDescription('Disable all effects on the captcha image.'),
    Option::create(null, 'no-distortion', GetOpt::NO_ARGUMENT)
        ->setDescription('Disable distortion.'),
    Option::create(null, 'fake-rand', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Fake rand for static designs. Float. Fixes random function. 0.0 means min, 1.0 means max.'),
    Option::create(null, 'seq', GetOpt::NO_ARGUMENT)
        ->setDescription('Sequential generation'),
    Option::create(null, 'exhaust', GetOpt::NO_ARGUMENT)
        ->setDescription('Generate all captchas possibles. Implies --seq and ignores -c'),
    Option::create('?', 'help', GetOpt::NO_ARGUMENT)
        ->setDescription('Show this help and quit'),
]);

$getOpt->addOperands([
    Operand::create('dest', Operand::REQUIRED)
        ->setDescription('Destination directory for the captcha image'),
    Operand::create('suffix', Operand::OPTIONAL)
        ->setDescription('Suffix for the filename. Filenames follow the "$label$suffix.jpg" format. If not specified, a random suffix is assigned')
]);

// Process arguments and handle errors
try {
    try {
        $getOpt->process();
    } catch (Missing $exception) {
        if (!$getOpt->getOption('help')) {
            throw $exception;
        }
    }
} catch (ArgumentException $exception) {
    file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
    echo PHP_EOL . $getOpt->getHelpText();
    exit(1);
}


// Show help and exit if requested
if ($getOpt->getOption('help')) {
    echo $getOpt->getHelpText();
    exit(0);
}

if ($getOpt->getOption('fake-rand')) {
    $seq = ((float)$getOpt->getOption('fake-rand')) ?? 0;

    class CaptchaBuilderFaked extends CaptchaBuilder {

        protected function rand($min, $max)
        {
            global $seq;
            return (int)($min + ($seq * ($max - $min)));
        }
    }

    $impl = 'CaptchaBuilderFaked';
}
else $impl = 'Gregwar\Captcha\CaptchaBuilder';

if ($getOpt->getOption('seq') || $getOpt->getOption('exhaust')) {
    class PhraseBuilderFaked implements PhraseBuilderInterface {
        private int $length;
        private array $charset;
        private int $counter = 0;

        public function __construct($length, $charset) {
            $this->length = $length;
            $this->charset = is_array($charset) ? $charset : str_split($charset);
        }

        public function build() {
            $base = count($this->charset);
            $number = $this->counter++;

            $result = '';
            for ($i = 0; $i < $this->length; $i++) {
                $result .= $this->charset[$number % $base];
                $number = intdiv($number, $base);
            }

            return $result;
        }

        public function niceize($str) {
            return $str;
        }
    }

    $implpb = "PhraseBuilderFaked";
} else $implpb = "Gregwar\Captcha\PhraseBuilder";


// Get options and operands
$phraseCount = (int)($getOpt->getOption('phrasecount') ?? 3);
$alphabet = $getOpt->getOption('alphabet') ?? '0123456789';
$dest = rtrim($getOpt->getOperand('dest'), '/') . '/';

$suffix = $getOpt->getOperand('suffix') ?? ("_" . rand());

$phraseBuilder = new $implpb($phraseCount, $alphabet);

if ($getOpt->getOption('exhaust')) {
    $todo = pow(strlen($alphabet), $phraseCount) - 1;
}
else $todo = (int)($getOpt->getOption('c') ?? 1);


for ($i = 0; $i < $todo; ++$i) {

    if (!is_dir($dest) || !is_writable($dest)) {
        file_put_contents('php://stderr', "Error: Destination directory '$dest' does not exist or is not writable.\n");
        exit(1);
    }

    $captchaBuilder = new $impl(null, $phraseBuilder);

    if ($getOpt->getOption('no-distortion')) {
        $captchaBuilder->setDistorsion(false);
    }

    if ($getOpt->getOption('ignore-all-effects')) {
        $captchaBuilder->setIgnoreAllEffects(true);
    }

    $captchaBuilder->build((int)($getOpt->getOption('w') ?? 150), (int)($getOpt->getOption('h') ?? 40));

    // Generate phrase and sanitize filename
    $phrase = $captchaBuilder->getPhrase();
    $filename = "$phrase$suffix.jpg";
    $filepath = $dest . $filename;

    // Save CAPTCHA image
    $captchaBuilder->save($filepath, (int)($getOpt->getOption('quality') ?? 80));
}
