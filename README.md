## Description
This tool allows you to generate CAPTCHAs using `gregwar/captcha`.

## Usage
```sh
$ php captchagen/gen.php --help
```

```
Usage: captchagen/gen.php [options] <dest> [<suffix>] [operands]

Operands:
  <dest>      Destination directory for the captcha image
  [<suffix>]  Suffix for the filename. Filenames follow the "$label$suffix.jpg" format. If not specified, a random
              suffix is assigned

Options:
  -c <arg>              Count of captchas to create
  -w <arg>              Width, defaults to 150
  -h <arg>              Height, defaults to 40
  --quality <arg>       Jpeg quality. Defaults to 80
  --phrasecount <arg>   Number of characters for the phrase. Defaults to 3
  --alphabet <arg>      Alphabet to use for the phrase. Defaults to 0123456789
  --ignore-all-effects  Disable all effects on the captcha image.
  --no-distortion       Disable distortion.
  --fake-rand [<arg>]   Fake rand for static designs. Float. Fixes random function. 0.0 means min, 1.0 means max.
  --seq                 Sequential generation
  --exhaust             Generate all captchas possibles. Implies --seq and ignores -c
  -?, --help            Show this help and quit
```

## Examples

### Create training data
```sh
$ mkdir testdata && php captchagen/gen.php -w 300 -h 80 --phrasecount 3 --exhaust --alphabet "0123456789" ./testdata _1 && python train.py
```

Generates CAPTCHAs with `3` characters, with `0123456789` as the charset.
Flag `--exhaust` exhausts the space for the given `phrasecount` and `alphabet` - `3` and `0123456789` for this example, respectively, which is `000`, `001`, ...  `999`.
The dimensions of the images are all 300x80.
Images will be generated inside of the `testdata` folder and will have a `_1` suffix.
The first `3` characters of the filename are the labels of each respective CAPTCHA.
This script should generate `000_1.jpg` ... `999_1.jpg`.

### Create five random captchas
```sh
$ php captchagen/gen.php -c 5 --phrasecount 6 --fake-rand 0.1 . _1
```
Creates 5 images in the current working directory and will have a `_1` suffix.
The dimensions of the images are all 150x40.
The first `6` characters of the filename are the labels of each respective CAPTCHA.
Distortions and other kinds of randomness are frozen - these five should share the same distortions.
### Deploy on Collab

```
!apt-get install php php-gd composer
!git clone https://github.com/gonzalobugueno/captchagen
!composer install -d captchagen/
!mkdir testdata
!php captchagen/gen.php -w 300 -h 80 --phrasecount 3 --exhaust --alphabet "0123456789" ./testdata _1
```
Sets up the script and makes captchas as described by the first example 