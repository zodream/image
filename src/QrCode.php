<?php
namespace Zodream\Image;
/**
 * 二维码
 * http://phpqrcode.sourceforge.net/
 * https://sourceforge.net/projects/phpqrcode/
 * @author Jason
 * @time 2015-12-1
 */
class QrCode extends Image {

    protected $level = 0;

    protected $size = 6;

    /**
     * 容错率
     * @param $level
     * @return $this
     */
    public function setLevel($level) {
        $this->level = $level;
        return $this;
    }

    /**
     * 尺寸
     * @param $size
     * @return $this
     */
    public function setSize($size) {
        $this->size = $size;
        return $this;
    }

    /**
     * 生成二维码
     * @param string $value
     * @return $this
     */
	public function create($value) {
		$this->image = \QRcode::png((string)$value, false, $this->level, $this->size, 2);
		return $this;
	}

    /**
     * 添加LOGO
     * @param string|Image|resource $logo
     * @return $this
     */
	public function addLogo($logo) {
		if (!$logo instanceof Image) {
			$logo = new Image($logo);
		}
		$width = ($this->width - $logo->width) / 2;
		$logoWidth = $this->width / 5;
		$this->copyFromWithReSampling($logo,
			$width,
			$width,
			0,
			0,
			$logo->width,
			$logo->height,
			$logoWidth,
			$this->height * $logo->width / $logoWidth
		);
		$logo->close();
		return $this;
	}

    /**
     * 发送邮件二维码
     * @param $email
     * @param null $subject
     * @param null $body
     * @return QrCode
     */
	public function email($email, $subject = null, $body = null) {
	    $email = 'mailto:'.$email;
	    if (!is_null($subject) || !is_null($body)) {
	        $email .= '?'.http_build_query(compact('subject', 'body'));
        }
	    return $this->create($email);
    }

    /**
     * 地理位置二维码
     * @param $latitude
     * @param $longitude
     * @return QrCode
     */
    public function geo($latitude, $longitude) {
	    return $this->create(sprintf('geo:%s,%s', $latitude, $longitude));
    }

    /**
     * 电话二维码
     * @param $phone
     * @return QrCode
     */
    public function tel($phone) {
        return $this->create('tel:'.$phone);
    }

    /**
     * 发送短信二维码
     * @param $phone
     * @param null $message
     * @return QrCode
     */
    public function sms($phone, $message = null) {
        $phone = 'sms:'.$phone;
        if (!is_null($message)) {
            $phone .= ':'. $message;
        }
        return $this->create($phone);
    }

    /**
     * WIFI 二维码
     * @param string $ssid  网络的SSID
     * @param string $password
     * @param string $encryption   WPA/WEP
     * @param bool $hidden true/false  是否是隐藏网络
     * @return QrCode
     */
    public function wifi($ssid = null, $password = null, $encryption = null, $hidden = null) {
        $wifi = 'WIFI:';
        if (!is_null($encryption)) {
            $wifi .= 'T:'.$encryption.';';
        }
        if (!is_null($ssid)) {
            $wifi .= 'S:'.$ssid.';';
        }
        if (!is_null($password)) {
            $wifi .= 'P:'.$password.';';
        }
        if (!is_null($hidden)) {
            $wifi .= 'H:'.($hidden === true ? 'true' : 'false').';';
        }
        return $this->create($wifi);
    }

    /**
     * 比特币
     * @param $address
     * @param $amount
     * @param $label
     * @param $message
     * @param $returnAddress
     * @return QrCode
     */
    public function btc($address, $amount, $label, $message, $returnAddress) {
        return $this->create(sprintf('bitcoin:%s?%s', $address, http_build_query([
            'amount'    => $amount,
            'label'     => $label,
            '$message'  => $message,
            'r'         => $returnAddress,
        ])));
    }
}