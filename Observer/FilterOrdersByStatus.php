<?php
namespace MagoArab\OrderStatusPermissions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\AuthorizationInterface;

/**
 * يقوم هذا الـ Observer بفلترة الطلبات في الـ Admin Grid 
 * بحيث لا تظهر إلا بالحالات المسموحة للمستخدم الحالي.
 */
class FilterOrdersByStatus implements ObserverInterface
{
    /**
     * @var AuthSession
     */
    protected $authSession;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    public function __construct(
        AuthSession $authSession,
        AuthorizationInterface $authorization
    ) {
        $this->authSession = $authSession;
        $this->authorization = $authorization;
    }

    /**
     * ينفّذ قبل تحميل Grid الطلبات في لوحة التحكم
     */
    public function execute(Observer $observer)
    {
        // الحصول على الـ collection الخاص بالطلبات
        $collection = $observer->getOrderGridCollection();
        if (!$collection) {
            return;
        }

        // الحصول على المستخدم الحالي في لوحة التحكم
        $user = $this->authSession->getUser();
        if (!$user) {
            return;
        }

        // جمع الحالات المسموحة من الـ ACL Resources (التي ينشئها البلجن)
        $allowedStatuses = $this->getAllowedStatusesFromACL();

        // إذا القائمة فاضية => لا يملك أي حالة => فلتر يمنع عرض الطلبات
        if (empty($allowedStatuses)) {
            $collection->addFieldToFilter('entity_id', 0);
            return;
        }

        // فلترة الطلبات لعرض فقط الحالات المسموحة
        $collection->addFieldToFilter('status', ['in' => $allowedStatuses]);
    }

    /**
     * الحصول على قائمة الحالات التي يملك المستخدم صلاحية مشاهدتها
     * بالاعتماد على موارد الـ ACL التي ينشئها البلجن ديناميكيًا.
     */
    private function getAllowedStatusesFromACL()
    {
        // 1) احصل على جميع الحالات التي أنشأها البلجن كموارد ACL.
        //    يجب أن تربطها بمنطق الإضافة الفعلي الذي يولّد الموارد.
        //    مثلاً إذا كان لديك كلاس/دالة في البلجن يسترجع الحالات، استدعه هنا.
        $allStatuses = $this->getAllCustomStatuses(); // <-- عدّلها لتتوافق مع بلجنك

        $allowed = [];
        foreach ($allStatuses as $statusCode) {
            // يفترض أن اسم الـ ACL Resource يأخذ شكل:
            // "MagoArab_OrderStatusPermissions::status_<status_code>"
            $resourceId = "MagoArab_OrderStatusPermissions::status_{$statusCode}";

            // إذا الـ Role لديه تصريح على هذا الـ Resource => نضيفه
            if ($this->authorization->isAllowed($resourceId)) {
                $allowed[] = $statusCode;
            }
        }

        return $allowed;
    }

    /**
     * دالة ترجع جميع الحالات (status codes) التي يولّد لها البلجن ACL Resources
     * عدّلها حسب منطق بلجنك (قد يقرأها من DB أو من ملف XML ... إلخ).
     */
    private function getAllCustomStatuses()
    {
        // هذه مجرد قائمة مثالية.
        // في بلجنك قد يكون هناك كود آخر لجلب الحالات الفعلية.
        return [
            'pending',
            'processing',
            'canceled',
            'complete',
            'suspected_fraud',
            'on_hold',
            'payment_review',
            // ... إلخ
        ];
    }
}
