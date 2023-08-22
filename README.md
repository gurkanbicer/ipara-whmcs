# WHMCS iPara Gateway Module

iPara Bilgi Teknolojileri A.Ş. (ipara.com.tr) ödeme kuruluşu aracılığıyla WHMCS üzerinde ödeme almak için hazırlanmış bir ödeme modülüdür.

### Geliştiriciye Notlar

- **binQuery.php** dosyası iPara üzerinden BIN sorgulaması yapıp, taksitlendirme seçeneklerini sunabilmeniz için hazırlanmıştır. Kullanıcı kart bilgisini girdikten sonra kart bilgilerini bu dosyaya jQuery Ajax ile POST edebilir ve taksitlendirme seçeneklerini alabilirsiniz.
  - **checkout.tpl** içerisinde ve **invoice-payment.tpl** içerisinde kredi kartı bilgisi girildikten sonra, bu kredi kart bilgisini binQuery.php dosyasına ajax ile post edebilir ve çıkan html yanıtı temanızda ilgili alana yazdırabilirsiniz.
  - Eğer taksitlendirme kullanmak istemiyorsanız aşağıdaki gibi hidden olarak veri gönderebilirsiniz.
    - `<input type="hidden" name="iparam_installment" value="1">`
- Başarılı ve başarısız ödemelerin ardından kullanıcı `modules/gateways/callback/iparam/payment-success.php` ve `modules/gateways/callback/iparam/payment-error.php` sayfalarına yönlendirilir. Bu sayfalara dilerseniz ekstra olarak logo gibi materyaller ekleyebilirsinz.
- Ödemenin başarılı ve başarısız olduğunun geri dönüşünü almak ve işlemek için de ipara.com.tr mağaza ayarlarınızın bulunduğu bölümde, Webhooks kısmından **payment.api.auth** ve **payment.api.threed** için `modules/gateways/callback/iparam.php` adresinizi ekleyerek yapabilirsiniz. Bu callback dosyanızı tanımlamamanız halinde ödeme işlenmeyecek ve gateway log üzerinde veri göremeyeceksiniz.

### Kullanım ve Geliştirme

WHMCS iPara Ödeme Modülünü ücretsiz olarak kullanabilir, ihtiyaçlarınız doğrultusunda değiştirebilirsiniz. Teknik destek için WHMCS'nin ve iPara'nın yardım dökümanlarına gözatabilirisiniz.

WHMCS ödeme modülü üzerinde hata bildiriminde veya katkıda bulunmak ya da sorularınızı yöneltmek için gurkan@veridyen.com adresine bir email gönderebilirsiniz. Ancak, ücretli veya ücretsiz olarak modülün özelleştirilmesinde ve entegrasyonunda destek sağlamamaktayız. Herkesin kullanımına uygun bir şekilde Veridyen adına açık kaynak olarak modülü paylaşmak istedik.

![image](https://github.com/gurkanbicer/ipara-whmcs/assets/5042349/5adafaf1-cc6c-40b4-bfe7-fac00bd99f35)

https://www.veridyen.com

### Yardım Linkleri

- https://dev.ipara.com.tr/
- https://developers.whmcs.com/payment-gateways/
