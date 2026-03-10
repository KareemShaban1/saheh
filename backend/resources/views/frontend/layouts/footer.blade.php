<!-- FOOTER -->
	<footer>
		<div class="footer-inner">
			<div>
				<a class="footer-logo" href="{{ url('/') }}">صحيح</a>
				<p class="footer-desc">ربط المرضى بعيادات ومختبرات ومراكز أشعة موثوقة في المنطقة. رعاية صحية ذات جودة، في متناول الجميع.</p>
				<div class="footer-social">
					<a class="social-icon" href="#" aria-label="تويتر"><i class="fab fa-twitter"></i></a>
					<a class="social-icon" href="#" aria-label="لينكد إن"><i class="fab fa-linkedin-in"></i></a>
					<a class="social-icon" href="#" aria-label="فيسبوك"><i class="fab fa-facebook-f"></i></a>
				</div>
			</div>
			<div class="footer-col">
				<h5>المنصة</h5>
				<ul>
					<li><a href="{{ route('clinics') }}">العيادات</a></li>
					<li><a href="{{ route('medical-laboratories') }}">المختبرات</a></li>
					<li><a href="{{ route('radiology-centers') }}">مراكز الأشعة</a></li>
				</ul>
			</div>
			<div class="footer-col">
				<h5>لمقدمي الخدمات</h5>
				<ul>
					<li><a href="{{ route('register-clinic') }}">تسجيل عيادة</a></li>
					<li><a href="{{ route('register-medical-laboratory') }}">تسجيل مختبر</a></li>
					<li><a href="{{ route('register-radiology-center') }}">تسجيل مركز أشعة</a></li>
				</ul>
			</div>
			<div class="footer-col">
				<h5>الشركة</h5>
				<ul>
					<li><a href="#social">من نحن</a></li>
					<li><a href="#how">كيف نعمل</a></li>
					<li><a href="#providers">شبكتنا</a></li>
				</ul>
			</div>
		</div>
		<div class="footer-bottom">
			<span>© {{ date('Y') }} صحيح. جميع الحقوق محفوظة.</span>
			<div class="footer-bottom-links">
				<a href="#">سياسة الخصوصية</a>
				<a href="#">الشروط والأحكام</a>
			</div>
		</div>
	</footer>
