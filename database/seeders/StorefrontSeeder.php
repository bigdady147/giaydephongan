<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class StorefrontSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'contact' => [
                'hotline' => '0909 000 000',
                'email' => 'lienhe@giaydephongan.vn',
                'address' => '123 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM',
                'open_hours' => '8:00 – 21:00 (Thứ 2 – Chủ nhật)',
            ],
            'social' => [
                'facebook_url' => 'https://facebook.com/giaydephongan',
                'zalo_url' => 'https://zalo.me/0909000000',
                'messenger_url' => 'https://m.me/giaydephongan',
                'instagram_url' => '',
            ],
            'footer' => [
                'business_name' => 'Hộ kinh doanh Giày dép Hồng An',
                'business_registration' => 'GPKD số 0123456789 do UBND Quận 1 cấp ngày 01/01/2020',
                'copyright' => '© 2026 Giày dép Hồng An. Bảo lưu mọi quyền.',
            ],
            'usp' => [
                'usp_1' => 'Da bò thật 100%',
                'usp_2' => 'Bảo hành 12 tháng',
                'usp_3' => 'Freeship đơn từ 500K',
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
            }
        }

        $pages = [
            ['title' => 'Giới thiệu', 'slug' => 'gioi-thieu', 'content' => '<p>Giày dép Hồng An — hơn 10 năm đồng hành cùng phái mạnh Việt với những đôi giày da thật, đóng thủ công tỉ mỉ.</p>'],
            ['title' => 'Chính sách đổi trả', 'slug' => 'chinh-sach-doi-tra', 'content' => '<p>Đổi size/mẫu trong 7 ngày với sản phẩm chưa qua sử dụng, còn nguyên hộp và phụ kiện.</p>'],
            ['title' => 'Chính sách bảo hành', 'slug' => 'chinh-sach-bao-hanh', 'content' => '<p>Bảo hành keo, chỉ, đế 12 tháng. Hỗ trợ sửa chữa trọn đời với chi phí ưu đãi.</p>'],
            ['title' => 'Chính sách giao hàng', 'slug' => 'chinh-sach-giao-hang', 'content' => '<p>Giao toàn quốc 2–5 ngày. Miễn phí giao hàng cho đơn từ 500.000₫.</p>'],
            ['title' => 'Hướng dẫn chọn size', 'slug' => 'huong-dan-chon-size', 'content' => '<p>Đặt bàn chân lên tờ giấy, đánh dấu gót và ngón dài nhất, đo khoảng cách rồi cộng thêm 0,5cm.</p><table><tr><th>Chiều dài chân (cm)</th><th>Size</th></tr><tr><td>24,5</td><td>39</td></tr><tr><td>25</td><td>40</td></tr><tr><td>25,5</td><td>41</td></tr><tr><td>26</td><td>42</td></tr><tr><td>26,5</td><td>43</td></tr></table>'],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page + ['is_active' => true]);
        }
    }
}
